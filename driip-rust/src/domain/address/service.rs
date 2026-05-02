use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{normalize_for_courier, Address};
use super::repository::AddressRepository;

pub struct AddressService;

impl AddressService {
    /// Validate that an address is not blocked. Returns the address if clean/flagged,
    /// errors if blocked.
    pub async fn validate_not_blocked(
        pool: &PgPool,
        address_id: Uuid,
    ) -> Result<Address, AppError> {
        let addr = AddressRepository::find_by_id(pool, address_id).await?;
        if addr.status == "blocked" {
            return Err(AppError::Validation(
                format!(
                    "Address has been blocked due to delivery issues{}",
                    addr.block_reason
                        .as_ref()
                        .map(|r| format!(": {r}"))
                        .unwrap_or_default()
                )
                .into(),
            ));
        }
        Ok(addr)
    }

    /// Called by OrderService when an order is cancelled or delivery fails.
    /// Increments the strike count on the order's shipping address.
    pub async fn auto_flag_on_cancel(pool: &PgPool, order_id: Uuid) -> Result<(), AppError> {
        let row: Option<(Option<Uuid>,)> =
            sqlx::query_as("SELECT shipping_address_id FROM orders WHERE id = $1")
                .bind(order_id)
                .fetch_optional(pool)
                .await
                .map_err(AppError::Database)?;

        if let Some((Some(address_id),)) = row {
            let updated = AddressRepository::increment_strike(pool, address_id).await?;
            tracing::info!(
                "Address {} strike_count incremented to {} (status={})",
                updated.id,
                updated.strike_count,
                updated.status
            );
        }

        Ok(())
    }

    /// Load a warehouse's address and normalize it for a given courier (e.g. "ghtk").
    #[allow(dead_code)]
    pub async fn warehouse_pickup_for_courier(
        pool: &PgPool,
        warehouse_id: Uuid,
        courier_key: &str,
    ) -> Result<serde_json::Value, AppError> {
        let addr = AddressRepository::find_by_warehouse(pool, warehouse_id)
            .await?
            .ok_or_else(|| AppError::NotFound("Warehouse has no linked address".into()))?;
        Ok(normalize_for_courier(&addr, courier_key))
    }

    /// Create a new address and link it to a customer as their default.
    pub async fn create_for_customer(
        pool: &PgPool,
        customer_id: Uuid,
        input: super::model::CreateAddress,
    ) -> Result<Address, AppError> {
        let addr = AddressRepository::create(pool, input).await?;
        AddressRepository::link_to_customer(pool, addr.id, customer_id, true).await?;
        Ok(addr)
    }
}
