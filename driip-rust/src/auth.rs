use std::collections::HashSet;

use axum::{
    extract::{FromRef, FromRequestParts},
    http::{request::Parts, HeaderMap},
};
use chrono::{Duration, Utc};
use jsonwebtoken::{decode, encode, DecodingKey, EncodingKey, Header, Validation};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

use crate::{errors::AppError, state::AppState};

// ── Claims ─────────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct Claims {
    pub sub: Uuid,       // staff_id
    pub role: String,    // admin | manager | staff | readonly
    pub exp: i64,        // unix timestamp expiry
    pub iat: i64,        // issued at
    pub kind: TokenKind, // access | refresh
}

#[derive(Debug, Serialize, Deserialize, Clone, PartialEq)]
#[serde(rename_all = "lowercase")]
pub enum TokenKind {
    Access,
    Refresh,
}

// ── Permissions ────────────────────────────────────────────────────────────

#[derive(Debug, Clone, Copy, PartialEq, Eq, Hash)]
pub enum Permission {
    // Staff
    StaffList,
    StaffRead,
    StaffCreate,
    StaffUpdate,
    StaffDelete,
    // Order
    OrderList,
    OrderRead,
    OrderCreate,
    OrderUpdate,
    OrderDelete,
    OrderConfirm,
    OrderCancel,
    OrderSetPriority,
    OrderReallocate,
    // Product
    ProductList,
    ProductRead,
    ProductCreate,
    ProductUpdate,
    ProductDelete,
    // Customer
    CustomerList,
    CustomerRead,
    CustomerCreate,
    CustomerUpdate,
    CustomerDelete,
    // Inventory
    InventoryList,
    InventoryRead,
    InventoryCreate,
    InventoryUpdate,
    InventoryDelete,
    InventoryReserve,
    // Warehouse
    WarehouseList,
    WarehouseRead,
    WarehouseCreate,
    WarehouseUpdate,
    WarehouseDelete,
    // Fulfillment
    FulfillmentRead,
    FulfillmentBook,
    FulfillmentCancel,
    FulfillmentManageCatalog,
    FulfillmentManageFees,
    // Purchase Order
    PurchaseOrderList,
    PurchaseOrderRead,
    PurchaseOrderCreate,
    PurchaseOrderUpdate,
    PurchaseOrderDelete,
    PurchaseOrderReceive,
    // Notification
    NotificationList,
    NotificationMarkRead,
}

/// Return the set of permissions granted to a given role.
pub fn permissions_for_role(role: &str) -> HashSet<Permission> {
    let mut perms = HashSet::new();
    match role {
        "admin" => {
            use Permission::*;
            perms.extend([
                StaffList,
                StaffRead,
                StaffCreate,
                StaffUpdate,
                StaffDelete,
                OrderList,
                OrderRead,
                OrderCreate,
                OrderUpdate,
                OrderDelete,
                OrderConfirm,
                OrderCancel,
                OrderSetPriority,
                OrderReallocate,
                ProductList,
                ProductRead,
                ProductCreate,
                ProductUpdate,
                ProductDelete,
                CustomerList,
                CustomerRead,
                CustomerCreate,
                CustomerUpdate,
                CustomerDelete,
                InventoryList,
                InventoryRead,
                InventoryCreate,
                InventoryUpdate,
                InventoryDelete,
                InventoryReserve,
                WarehouseList,
                WarehouseRead,
                WarehouseCreate,
                WarehouseUpdate,
                WarehouseDelete,
                FulfillmentRead,
                FulfillmentBook,
                FulfillmentCancel,
                FulfillmentManageCatalog,
                FulfillmentManageFees,
                PurchaseOrderList,
                PurchaseOrderRead,
                PurchaseOrderCreate,
                PurchaseOrderUpdate,
                PurchaseOrderDelete,
                PurchaseOrderReceive,
                NotificationList,
                NotificationMarkRead,
            ]);
        }
        "manager" => {
            use Permission::*;
            perms.extend([
                StaffList,
                StaffRead,
                OrderList,
                OrderRead,
                OrderCreate,
                OrderUpdate,
                OrderDelete,
                OrderConfirm,
                OrderCancel,
                OrderSetPriority,
                OrderReallocate,
                ProductList,
                ProductRead,
                ProductCreate,
                ProductUpdate,
                ProductDelete,
                CustomerList,
                CustomerRead,
                CustomerCreate,
                CustomerUpdate,
                CustomerDelete,
                InventoryList,
                InventoryRead,
                InventoryCreate,
                InventoryUpdate,
                InventoryDelete,
                InventoryReserve,
                WarehouseList,
                WarehouseRead,
                WarehouseCreate,
                WarehouseUpdate,
                WarehouseDelete,
                FulfillmentRead,
                FulfillmentBook,
                FulfillmentCancel,
                FulfillmentManageCatalog,
                FulfillmentManageFees,
                PurchaseOrderList,
                PurchaseOrderRead,
                PurchaseOrderCreate,
                PurchaseOrderUpdate,
                PurchaseOrderDelete,
                PurchaseOrderReceive,
                NotificationList,
                NotificationMarkRead,
            ]);
        }
        "staff" => {
            use Permission::*;
            perms.extend([
                OrderList,
                OrderRead,
                OrderCreate,
                OrderUpdate,
                OrderConfirm,
                OrderCancel,
                CustomerList,
                CustomerRead,
                CustomerCreate,
                CustomerUpdate,
                ProductList,
                ProductRead,
                InventoryList,
                InventoryRead,
                InventoryReserve,
                FulfillmentRead,
                FulfillmentBook,
                FulfillmentCancel,
                PurchaseOrderList,
                PurchaseOrderRead,
                PurchaseOrderCreate,
                NotificationList,
                NotificationMarkRead,
            ]);
        }
        "readonly" => {
            use Permission::*;
            perms.extend([
                StaffList,
                StaffRead,
                OrderList,
                OrderRead,
                ProductList,
                ProductRead,
                CustomerList,
                CustomerRead,
                InventoryList,
                InventoryRead,
                WarehouseList,
                WarehouseRead,
                FulfillmentRead,
                PurchaseOrderList,
                PurchaseOrderRead,
                NotificationList,
            ]);
        }
        _ => {}
    }
    perms
}

/// Verify that the authenticated context has the required permission.
pub fn check_permission(ctx: &AuthContext, perm: Permission) -> Result<(), AppError> {
    let perms = permissions_for_role(&ctx.role);
    if perms.contains(&perm) {
        Ok(())
    } else {
        Err(AppError::Forbidden)
    }
}

// ── Token generation ────────────────────────────────────────────────────────

pub fn create_access_token(
    staff_id: Uuid,
    role: &str,
    secret: &str,
    ttl_secs: u64,
) -> Result<String, AppError> {
    let now = Utc::now();
    let claims = Claims {
        sub: staff_id,
        role: role.to_string(),
        exp: (now + Duration::seconds(ttl_secs as i64)).timestamp(),
        iat: now.timestamp(),
        kind: TokenKind::Access,
    };
    encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(secret.as_bytes()),
    )
    .map_err(|e| AppError::Internal(format!("JWT encode error: {e}")))
}

pub fn create_refresh_token(
    staff_id: Uuid,
    role: &str,
    secret: &str,
    ttl_secs: u64,
) -> Result<String, AppError> {
    let now = Utc::now();
    let claims = Claims {
        sub: staff_id,
        role: role.to_string(),
        exp: (now + Duration::seconds(ttl_secs as i64)).timestamp(),
        iat: now.timestamp(),
        kind: TokenKind::Refresh,
    };
    encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(secret.as_bytes()),
    )
    .map_err(|e| AppError::Internal(format!("JWT encode error: {e}")))
}

pub fn verify_token(token: &str, secret: &str) -> Result<Claims, AppError> {
    decode::<Claims>(
        token,
        &DecodingKey::from_secret(secret.as_bytes()),
        &Validation::default(),
    )
    .map(|data| data.claims)
    .map_err(|e| AppError::Unauthorized(format!("Invalid token: {e}")))
}

// ── AuthContext extractor ───────────────────────────────────────────────────

/// Injected into protected handlers via `Extension<AuthContext>`.
/// Use `RequireRole` extractors for role-based guards.
#[derive(Debug, Clone)]
pub struct AuthContext {
    pub staff_id: Uuid,
    pub role: String,
}

impl<S> FromRequestParts<S> for AuthContext
where
    AppState: axum::extract::FromRef<S>,
    S: Send + Sync,
{
    type Rejection = AppError;

    async fn from_request_parts(parts: &mut Parts, state: &S) -> Result<Self, Self::Rejection> {
        let app_state = AppState::from_ref(state);
        let token = extract_bearer(&parts.headers)?;
        let claims = verify_token(token, &app_state.jwt_secret)?;

        if claims.kind != TokenKind::Access {
            return Err(AppError::Unauthorized("Expected access token".to_string()));
        }

        Ok(AuthContext {
            staff_id: claims.sub,
            role: claims.role,
        })
    }
}

fn extract_bearer(headers: &HeaderMap) -> Result<&str, AppError> {
    headers
        .get("authorization")
        .and_then(|v| v.to_str().ok())
        .and_then(|s| s.strip_prefix("Bearer "))
        .ok_or_else(|| AppError::Unauthorized("Missing or malformed Authorization header".into()))
}

// Role-based extractors removed; use `check_permission` instead.

// ── Customer JWT ────────────────────────────────────────────────────────────

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct CustomerClaims {
    pub sub: Uuid, // customer_id
    pub exp: i64,
    pub iat: i64,
    pub kind: TokenKind, // access | refresh
}

pub fn create_customer_access_token(
    customer_id: Uuid,
    secret: &str,
    ttl_secs: u64,
) -> Result<String, AppError> {
    let now = Utc::now();
    let claims = CustomerClaims {
        sub: customer_id,
        exp: (now + Duration::seconds(ttl_secs as i64)).timestamp(),
        iat: now.timestamp(),
        kind: TokenKind::Access,
    };
    encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(secret.as_bytes()),
    )
    .map_err(|e| AppError::Internal(format!("JWT encode error: {e}")))
}

pub fn create_customer_refresh_token(
    customer_id: Uuid,
    secret: &str,
    ttl_secs: u64,
) -> Result<String, AppError> {
    let now = Utc::now();
    let claims = CustomerClaims {
        sub: customer_id,
        exp: (now + Duration::seconds(ttl_secs as i64)).timestamp(),
        iat: now.timestamp(),
        kind: TokenKind::Refresh,
    };
    encode(
        &Header::default(),
        &claims,
        &EncodingKey::from_secret(secret.as_bytes()),
    )
    .map_err(|e| AppError::Internal(format!("JWT encode error: {e}")))
}

pub fn verify_customer_token(token: &str, secret: &str) -> Result<CustomerClaims, AppError> {
    decode::<CustomerClaims>(
        token,
        &DecodingKey::from_secret(secret.as_bytes()),
        &Validation::default(),
    )
    .map(|data| data.claims)
    .map_err(|e| AppError::Unauthorized(format!("Invalid token: {e}")))
}

// ── CustomerAuth extractor ─────────────────────────────────────────────────

#[derive(Debug, Clone)]
pub struct CustomerAuth {
    pub customer_id: Uuid,
}

impl<S> FromRequestParts<S> for CustomerAuth
where
    AppState: axum::extract::FromRef<S>,
    S: Send + Sync,
{
    type Rejection = AppError;

    async fn from_request_parts(parts: &mut Parts, state: &S) -> Result<Self, Self::Rejection> {
        let app_state = AppState::from_ref(state);
        let token = extract_bearer(&parts.headers)?;
        let claims = verify_customer_token(token, &app_state.jwt_secret)?;

        if claims.kind != TokenKind::Access {
            return Err(AppError::Unauthorized("Expected access token".to_string()));
        }

        Ok(CustomerAuth {
            customer_id: claims.sub,
        })
    }
}
