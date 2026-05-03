use std::{io::Cursor, sync::Arc};

use image::ImageFormat;
use reqwest::{header, Client};
use serde::{Deserialize, Serialize};
use serde_json::Value;
use tokio::sync::Mutex;
use uuid::Uuid;

use crate::config::Config;

/// B2 API credentials and endpoints
pub struct B2Client {
    account_id: String,
    application_key: String,
    bucket_id: String,
    /// Bucket name for B2 operations (currently unused but needed for future bucket switching)
    #[allow(dead_code)]
    bucket_name: String,
    /// CDN-friendly base URL (fblumi short domain or custom domain)
    cdn_base_url: String,
    http: Client,
    auth: Arc<Mutex<Option<B2Auth>>>,
}

#[derive(Clone, Deserialize)]
struct B2Auth {
    authorization_token: String,
    api_url: String,
    /// Direct download URL from B2 (for non-CDN fallback)
    #[allow(dead_code)]
    download_url: String,
}

/// B2 upload request structure (for future direct upload API)
#[derive(Serialize)]
#[allow(dead_code)]
struct B2UploadRequest {
    bucket_id: String,
    /// Target file path in bucket
    #[allow(dead_code)]
    file_name: String,
    content_type: String,
    file_contents: Vec<u8>,
}

/// B2 upload response (for future metadata extraction)
#[derive(Deserialize)]
#[allow(dead_code)]
struct B2UploadResponse {
    file_id: String,
    /// Full path in bucket
    #[allow(dead_code)]
    file_name: String,
}

/// B2 file metadata (for future bucket browsing API)
#[derive(Deserialize)]
#[allow(dead_code)]
struct B2File {
    file_id: String,
    /// Full path in bucket
    #[allow(dead_code)]
    file_name: String,
    content_type: String,
    content_length: i64,
}

impl B2Client {
    pub fn from_config(config: &Config) -> Option<Self> {
        let account_id = config.b2_account_id.clone()?;
        let application_key = config.b2_application_key.clone()?;
        let bucket_id = config.b2_bucket_id.clone()?;
        let bucket_name = config.b2_bucket_name.clone()?;
        // Prefer custom CDN domain if set, else use B2 direct
        let cdn_base_url = config
            .b2_cdn_url
            .clone()
            .unwrap_or_else(|| format!("https://f000.backblazeb2.com/file/{}", bucket_name));

        Some(Self {
            account_id,
            application_key,
            bucket_id,
            bucket_name,
            cdn_base_url,
            http: Client::new(),
            auth: Arc::new(Mutex::new(None)),
        })
    }

    /// Authenticate with B2 (lazy auth on first use)
    async fn auth(&self) -> Result<B2Auth, reqwest::Error> {
        // Check if we already have auth
        {
            let guard = self.auth.lock().await;
            if let Some(auth) = guard.clone() {
                return Ok(auth);
            }
        }

        // Need to authenticate
        use base64::Engine;
        let creds = base64::engine::general_purpose::STANDARD
            .encode(format!("{}:{}", self.account_id, self.application_key));
        let res = self
            .http
            .get("https://api.backblazeb2.com/b2api/v2/b2_authorize_account")
            .header(header::AUTHORIZATION, format!("Basic {}", creds))
            .send()
            .await?
            .json::<B2Auth>()
            .await?;

        let mut guard = self.auth.lock().await;
        *guard = Some(res.clone());
        Ok(res)
    }

    /// Upload file to B2 with optional thumbnail generation
    pub async fn upload(
        &self,
        file_name: &str,
        data: Vec<u8>,
        content_type: &str,
        generate_thumbnail: bool,
    ) -> Result<(String, String, Option<(String, String, i32, i32)>), Box<dyn std::error::Error>>
    {
        let auth = self.auth().await?;

        // Generate thumbnail for images if requested
        let thumb_info = if generate_thumbnail && content_type.starts_with("image/") {
            self.generate_thumbnail_and_upload(&auth, file_name, &data)
                .await
                .ok()
        } else {
            None
        };

        // Upload original
        let upload_url = self.get_upload_url(&auth).await?;
        let file_id = self
            .upload_to_b2(
                &upload_url,
                &auth.authorization_token,
                file_name,
                data,
                content_type,
            )
            .await?;

        Ok((file_id, file_name.to_string(), thumb_info))
    }

    /// Generate thumbnail and upload to B2
    async fn generate_thumbnail_and_upload(
        &self,
        auth: &B2Auth,
        original_name: &str,
        data: &[u8],
    ) -> Result<(String, String, i32, i32), Box<dyn std::error::Error>> {
        // Parse image
        let img = image::load_from_memory(data)?;
        let (_orig_w, _orig_h) = (img.width() as i32, img.height() as i32);

        // Resize to max 400px width (thumbnail)
        let thumb = img.resize(400, 400, image::imageops::FilterType::Lanczos3);
        let (thumb_w, thumb_h) = (thumb.width() as i32, thumb.height() as i32);

        // Encode as JPEG
        let mut thumb_bytes = Vec::new();
        thumb.write_to(&mut Cursor::new(&mut thumb_bytes), ImageFormat::Jpeg)?;

        // Upload thumbnail
        let thumb_name = format!("thumbs/{}_{}.jpg", Uuid::new_v4(), original_name);
        let upload_url = self.get_upload_url(auth).await?;
        let thumb_file_id = self
            .upload_to_b2(
                &upload_url,
                &auth.authorization_token,
                &thumb_name,
                thumb_bytes,
                "image/jpeg",
            )
            .await?;

        Ok((thumb_file_id, thumb_name, thumb_w, thumb_h))
    }

    /// Get upload URL from B2
    async fn get_upload_url(&self, auth: &B2Auth) -> Result<String, reqwest::Error> {
        let res = self
            .http
            .post(format!("{}/b2api/v2/b2_get_upload_url", auth.api_url))
            .header(header::AUTHORIZATION, &auth.authorization_token)
            .json(&serde_json::json!({ "bucketId": self.bucket_id }))
            .send()
            .await?
            .json::<serde_json::Value>()
            .await?;

        Ok(res["uploadUrl"].as_str().unwrap_or("").to_string())
    }

    /// Upload bytes to B2
    async fn upload_to_b2(
        &self,
        upload_url: &str,
        auth_token: &str,
        file_name: &str,
        data: Vec<u8>,
        content_type: &str,
    ) -> Result<String, reqwest::Error> {
        let res = self
            .http
            .post(upload_url)
            .header(header::AUTHORIZATION, auth_token)
            .header("X-Bz-File-Name", file_name)
            .header("Content-Type", content_type)
            .header("X-Bz-Content-Sha1", "do_not_verify") // Simpler for now
            .body(data)
            .send()
            .await?
            .json::<B2UploadResponse>()
            .await?;

        Ok(res.file_id)
    }

    /// Delete file from B2
    pub async fn delete(&self, file_id: &str) -> Result<(), Box<dyn std::error::Error>> {
        let auth = self.auth().await?;

        self.http
            .post(format!("{}/b2api/v2/b2_delete_file_version", auth.api_url))
            .header(header::AUTHORIZATION, &auth.authorization_token)
            .json(&serde_json::json!({
                "fileId": file_id,
                "fileName": "" // B2 needs both but fileName can be empty
            }))
            .send()
            .await?;

        Ok(())
    }

    /// Build public URL for serving (uses CDN if configured)
    pub fn get_url(&self, file_path: &str) -> String {
        format!("{}/{}", self.cdn_base_url, file_path)
    }

    /// Build thumbnail URL
    pub fn get_thumbnail_url(&self, thumb_path: &str) -> String {
        format!("{}/{}", self.cdn_base_url, thumb_path)
    }

    // ═════════════════════════════════════════════════════════════════════════
    // NEW: B2 Bucket Browsing
    // ═════════════════════════════════════════════════════════════════════════

    /// List files in B2 bucket with optional prefix (directory browsing)
    pub async fn list_files(
        &self,
        prefix: Option<&str>,
        limit: i64,
        start_file_name: Option<&str>,
    ) -> Result<Vec<B2BucketFile>, Box<dyn std::error::Error>> {
        let auth = self.auth().await?;

        let bucket_id = self.bucket_id.clone();

        let mut body = serde_json::json!({
            "bucketId": bucket_id,
            "maxFileCount": limit,
            "prefix": prefix.unwrap_or(""),
        });

        if let Some(start) = start_file_name {
            body["startFileName"] = serde_json::json!(start);
        }

        let res = self
            .http
            .post(format!("{}/b2api/v2/b2_list_file_names", auth.api_url))
            .header(header::AUTHORIZATION, &auth.authorization_token)
            .json(&body)
            .send()
            .await?
            .json::<Value>()
            .await?;

        let files = res["files"]
            .as_array()
            .unwrap_or(&vec![])
            .iter()
            .map(|f| {
                let file_name = f["fileName"].as_str().unwrap_or("").to_string();
                let is_thumbnail = file_name.contains("_thumb") || file_name.contains("/thumbs/");

                B2BucketFile {
                    file_id: f["fileId"].as_str().unwrap_or("").to_string(),
                    file_name: file_name.clone(),
                    content_type: f["contentType"]
                        .as_str()
                        .unwrap_or("application/octet-stream")
                        .to_string(),
                    size: f["size"].as_i64().unwrap_or(0),
                    uploaded_at: chrono::Utc::now(), // B2 doesn't return timestamp in this API
                    is_thumbnail,
                }
            })
            .collect();

        Ok(files)
    }

    /// List file versions (for deletion)
    pub async fn list_file_versions(
        &self,
        file_name: &str,
    ) -> Result<Vec<String>, Box<dyn std::error::Error>> {
        let auth = self.auth().await?;

        let res = self
            .http
            .post(format!("{}/b2api/v2/b2_list_file_versions", auth.api_url))
            .header(header::AUTHORIZATION, &auth.authorization_token)
            .json(&serde_json::json!({
                "bucketId": self.bucket_id,
                "startFileName": file_name,
                "maxFileCount": 100,
            }))
            .send()
            .await?
            .json::<Value>()
            .await?;

        let file_ids = res["files"]
            .as_array()
            .unwrap_or(&vec![])
            .iter()
            .filter(|f| f["fileName"].as_str() == Some(file_name))
            .map(|f| f["fileId"].as_str().unwrap_or("").to_string())
            .collect();

        Ok(file_ids)
    }
}

/// B2 file info for bucket browsing
#[derive(Debug, Clone)]
pub struct B2BucketFile {
    pub file_id: String,
    pub file_name: String,
    pub content_type: String,
    pub size: i64,
    pub uploaded_at: chrono::DateTime<chrono::Utc>,
    pub is_thumbnail: bool,
}

/// Image processing utilities
pub mod image_utils {
    use image::ImageFormat;
    use std::io::Cursor;

    /// Get image dimensions without full decode
    pub fn get_dimensions(data: &[u8]) -> Option<(i32, i32)> {
        image::load_from_memory(data)
            .ok()
            .map(|img| (img.width() as i32, img.height() as i32))
    }

    /// Create thumbnail (400px max dimension)
    /// Public utility for external thumbnail generation
    #[allow(dead_code)]
    pub fn create_thumbnail(data: &[u8]) -> Result<Vec<u8>, Box<dyn std::error::Error>> {
        let img = image::load_from_memory(data)?;
        let thumb = img.resize(400, 400, image::imageops::FilterType::Lanczos3);

        let mut output = Vec::new();
        thumb.write_to(&mut Cursor::new(&mut output), ImageFormat::Jpeg)?;
        Ok(output)
    }
}
