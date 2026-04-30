use std::{sync::Arc, time::Duration};

use moka::future::Cache;
use reqwest::Client;
use serde::{de::DeserializeOwned, Serialize};
use serde_json::Value;

/// TTL constants
pub const TTL_CATALOG: Duration = Duration::from_secs(60);
pub const TTL_INVENTORY: Duration = Duration::from_secs(10);
pub const TTL_ORDER: Duration = Duration::from_secs(30);

/// L1: in-process Moka cache (per warm Lambda instance)
#[derive(Clone)]
pub struct L1Cache {
    inner: Cache<String, Value>,
}

impl L1Cache {
    pub fn new() -> Self {
        Self {
            inner: Cache::builder()
                .max_capacity(1_000)
                .time_to_live(Duration::from_secs(60))
                .build(),
        }
    }

    pub async fn get<T: DeserializeOwned>(&self, key: &str) -> Option<T> {
        let val = self.inner.get(key).await?;
        serde_json::from_value(val).ok()
    }

    pub async fn set<T: Serialize>(&self, key: &str, value: &T, _ttl: Duration) {
        if let Ok(val) = serde_json::to_value(value) {
            self.inner.insert(key.to_string(), val).await;
        }
    }

    pub async fn invalidate(&self, key: &str) {
        self.inner.invalidate(key).await;
    }

    pub async fn invalidate_prefix(&self, prefix: &str) {
        // Collect keys first to avoid holding a reference across await
        let keys: Vec<String> = self
            .inner
            .iter()
            .filter(|(k, _)| k.starts_with(prefix))
            .map(|(k, _)| k.to_string())
            .collect();
        for k in keys {
            self.inner.invalidate(&k).await;
        }
    }
}

impl Default for L1Cache {
    fn default() -> Self {
        Self::new()
    }
}

/// L2: Upstash Redis via HTTP REST API (global coordinator across Lambda instances)
#[derive(Clone)]
pub struct L2Cache {
    client: Arc<Client>,
    base_url: String,
    token: String,
}

impl L2Cache {
    pub fn new(base_url: String, token: String) -> Self {
        Self {
            client: Arc::new(Client::new()),
            base_url,
            token,
        }
    }

    pub async fn get<T: DeserializeOwned>(&self, key: &str) -> Option<T> {
        let url = format!("{}/get/{}", self.base_url, key);
        let resp = self
            .client
            .get(&url)
            .bearer_auth(&self.token)
            .send()
            .await
            .ok()?;

        let body: Value = resp.json().await.ok()?;
        let raw = body.get("result")?.as_str()?;
        serde_json::from_str(raw).ok()
    }

    pub async fn set<T: Serialize>(&self, key: &str, value: &T, ttl: Duration) {
        let Ok(json_str) = serde_json::to_string(value) else {
            return;
        };
        let url = format!("{}/set/{}", self.base_url, key);
        let _ = self
            .client
            .post(&url)
            .bearer_auth(&self.token)
            .query(&[("ex", ttl.as_secs().to_string())])
            .json(&json_str)
            .send()
            .await;
    }

    pub async fn del(&self, key: &str) {
        let url = format!("{}/del/{}", self.base_url, key);
        let _ = self
            .client
            .post(&url)
            .bearer_auth(&self.token)
            .send()
            .await;
    }
}

/// Unified two-level cache: L1 (Moka) → L2 (Upstash)
#[derive(Clone)]
pub struct AppCache {
    pub l1: L1Cache,
    pub l2: Option<L2Cache>,
}

impl AppCache {
    pub fn new(upstash_url: Option<String>, upstash_token: Option<String>) -> Self {
        let l2 = match (upstash_url, upstash_token) {
            (Some(url), Some(token)) => Some(L2Cache::new(url, token)),
            _ => None,
        };
        Self {
            l1: L1Cache::new(),
            l2,
        }
    }

    /// Read-through: L1 miss → L2 miss → None (caller fetches from DB and calls set)
    pub async fn get<T: DeserializeOwned + Serialize>(&self, key: &str) -> Option<T> {
        // L1 hit
        if let Some(val) = self.l1.get::<T>(key).await {
            return Some(val);
        }
        // L2 hit → backfill L1
        if let Some(l2) = &self.l2 {
            if let Some(val) = l2.get::<T>(key).await {
                self.l1.set(key, &val, TTL_CATALOG).await;
                return Some(val);
            }
        }
        None
    }

    /// Write-through: populate both levels
    pub async fn set<T: Serialize + DeserializeOwned>(
        &self,
        key: &str,
        value: &T,
        ttl: Duration,
    ) {
        self.l1.set(key, value, ttl).await;
        if let Some(l2) = &self.l2 {
            l2.set(key, value, ttl).await;
        }
    }

    /// Invalidate both levels
    pub async fn invalidate(&self, key: &str) {
        self.l1.invalidate(key).await;
        if let Some(l2) = &self.l2 {
            l2.del(key).await;
        }
    }

    /// Invalidate all L1 keys matching prefix (L2 keys invalidated individually at write time)
    pub async fn invalidate_prefix(&self, prefix: &str) {
        self.l1.invalidate_prefix(prefix).await;
    }
}
