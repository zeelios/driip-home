use sqlx::PgPool;
use uuid::Uuid;

use crate::errors::AppError;

use super::model::{
    PoFilter, PurchaseOrder, PurchaseOrderDetail, PurchaseOrderItem, CreatePurchaseOrder,
    ReceivePurchaseOrder, UpdatePurchaseOrder,
};
use crate::domain::notification::model::CreateNotification;
use crate::domain::notification::repository::NotificationRepository;

pub struct PurchaseOrderRepository;

impl PurchaseOrderRepository {
    pub async fn list(pool: &PgPool, filter: &PoFilter) -> Result<Vec<PurchaseOrder>, AppError> {
        let page = filter.page.unwrap_or(1).max(1);
        let per_page = filter.per_page.unwrap_or(20).min(100);
        let offset = (page - 1) * per_page;

        sqlx::query_as::<_, PurchaseOrder>(
            r#"
            SELECT * FROM purchase_orders
            WHERE ($1::text IS NULL OR status = $1)
            ORDER BY created_at DESC
            LIMIT $2 OFFSET $3
            "#,
        )
        .bind(&filter.status)
        .bind(per_page)
        .bind(offset)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)
    }

    pub async fn find_by_id(pool: &PgPool, id: Uuid) -> Result<PurchaseOrderDetail, AppError> {
        let order = sqlx::query_as::<_, PurchaseOrder>(
            "SELECT * FROM purchase_orders WHERE id = $1",
        )
        .bind(id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Purchase order not found".into()))?;

        let items = sqlx::query_as::<_, PurchaseOrderItem>(
            "SELECT * FROM purchase_order_items WHERE purchase_order_id = $1 ORDER BY id",
        )
        .bind(id)
        .fetch_all(pool)
        .await
        .map_err(AppError::Database)?;

        Ok(PurchaseOrderDetail { order, items })
    }

    pub async fn create(
        pool: &PgPool,
        input: CreatePurchaseOrder,
        created_by: Uuid,
    ) -> Result<PurchaseOrderDetail, AppError> {
        let mut tx = pool.begin().await.map_err(AppError::Database)?;

        let order = sqlx::query_as::<_, PurchaseOrder>(
            r#"
            INSERT INTO purchase_orders (id, supplier_name, status, expected_date, notes, created_by, created_at, updated_at)
            VALUES (gen_random_uuid(), $1, 'draft', $2, $3, $4, NOW(), NOW())
            RETURNING *
            "#,
        )
        .bind(&input.supplier_name)
        .bind(input.expected_date)
        .bind(&input.notes)
        .bind(created_by)
        .fetch_one(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        let mut items = Vec::with_capacity(input.items.len());
        for item in input.items {
            let row = sqlx::query_as::<_, PurchaseOrderItem>(
                r#"
                INSERT INTO purchase_order_items
                    (id, purchase_order_id, product_id, warehouse_id, ordered_qty, received_qty, unit_cost_cents)
                VALUES (gen_random_uuid(), $1, $2, $3, $4, 0, $5)
                RETURNING *
                "#,
            )
            .bind(order.id)
            .bind(item.product_id)
            .bind(item.warehouse_id)
            .bind(item.ordered_qty)
            .bind(item.unit_cost_cents)
            .fetch_one(&mut *tx)
            .await
            .map_err(AppError::Database)?;
            items.push(row);
        }

        tx.commit().await.map_err(AppError::Database)?;
        Ok(PurchaseOrderDetail { order, items })
    }

    pub async fn update(
        pool: &PgPool,
        id: Uuid,
        input: UpdatePurchaseOrder,
    ) -> Result<PurchaseOrder, AppError> {
        let current = sqlx::query_as::<_, PurchaseOrder>(
            "SELECT * FROM purchase_orders WHERE id = $1",
        )
        .bind(id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Purchase order not found".into()))?;

        if current.status != "draft" {
            return Err(AppError::Validation(
                "Only draft purchase orders can be updated".into(),
            ));
        }

        sqlx::query_as::<_, PurchaseOrder>(
            r#"
            UPDATE purchase_orders
            SET supplier_name = $2,
                expected_date = $3,
                notes         = $4,
                updated_at    = NOW()
            WHERE id = $1
            RETURNING *
            "#,
        )
        .bind(id)
        .bind(input.supplier_name.unwrap_or(current.supplier_name))
        .bind(input.expected_date.or(current.expected_date))
        .bind(input.notes.or(current.notes))
        .fetch_one(pool)
        .await
        .map_err(AppError::Database)
    }

    /// Receive stock: increments inventory, updates received_qty, re-evaluates PO status,
    /// fires notifications for any affected orders whose inventory_status may have improved.
    pub async fn receive(
        pool: &PgPool,
        po_id: Uuid,
        input: ReceivePurchaseOrder,
    ) -> Result<PurchaseOrderDetail, AppError> {
        let current = sqlx::query_as::<_, PurchaseOrder>(
            "SELECT * FROM purchase_orders WHERE id = $1",
        )
        .bind(po_id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| AppError::NotFound("Purchase order not found".into()))?;

        if matches!(current.status.as_str(), "received" | "cancelled") {
            return Err(AppError::Validation(
                "Cannot receive a completed or cancelled purchase order".into(),
            ));
        }

        let mut tx = pool.begin().await.map_err(AppError::Database)?;

        for line in &input.items {
            // Fetch the PO item to get product_id + warehouse_id
            let poi = sqlx::query_as::<_, PurchaseOrderItem>(
                "SELECT * FROM purchase_order_items WHERE id = $1 AND purchase_order_id = $2",
            )
            .bind(line.purchase_order_item_id)
            .bind(po_id)
            .fetch_optional(&mut *tx)
            .await
            .map_err(AppError::Database)?
            .ok_or_else(|| AppError::NotFound("PO item not found".into()))?;

            // Upsert inventory: add stock to the right warehouse
            sqlx::query(
                r#"
                INSERT INTO inventory (id, product_id, warehouse_id, quantity, reserved_quantity, created_at, updated_at)
                VALUES (gen_random_uuid(), $1, $2, $3, 0, NOW(), NOW())
                ON CONFLICT (product_id, warehouse_id)
                DO UPDATE SET quantity = inventory.quantity + EXCLUDED.quantity,
                              updated_at = NOW()
                "#,
            )
            .bind(poi.product_id)
            .bind(poi.warehouse_id)
            .bind(line.received_qty)
            .execute(&mut *tx)
            .await
            .map_err(AppError::Database)?;

            // Update received_qty on PO item
            sqlx::query(
                "UPDATE purchase_order_items SET received_qty = received_qty + $2 WHERE id = $1",
            )
            .bind(line.purchase_order_item_id)
            .bind(line.received_qty)
            .execute(&mut *tx)
            .await
            .map_err(AppError::Database)?;
        }

        // Re-compute PO status
        let status_row = sqlx::query!(
            r#"
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN received_qty >= ordered_qty THEN 1 ELSE 0 END) AS fully_received
            FROM purchase_order_items
            WHERE purchase_order_id = $1
            "#,
            po_id,
        )
        .fetch_one(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        let total = status_row.total.unwrap_or(0);
        let full = status_row.fully_received.unwrap_or(0);
        let new_status = if full == total { "received" } else { "partial" };

        sqlx::query(
            "UPDATE purchase_orders SET status = $2, updated_at = NOW() WHERE id = $1",
        )
        .bind(po_id)
        .bind(new_status)
        .execute(&mut *tx)
        .await
        .map_err(AppError::Database)?;

        tx.commit().await.map_err(AppError::Database)?;

        // Fire broadcast notification to managers
        NotificationRepository::broadcast(
            pool,
            CreateNotification {
                staff_id: None,
                kind: "po_received",
                title: format!("Stock received: PO from {}", current.supplier_name),
                body: Some(format!("{} items received", input.items.len())),
                entity_type: Some("purchase_order"),
                entity_id: Some(po_id),
            },
        )
        .await?;

        Self::find_by_id(pool, po_id).await
    }

    pub async fn cancel(pool: &PgPool, id: Uuid) -> Result<PurchaseOrder, AppError> {
        let result = sqlx::query_as::<_, PurchaseOrder>(
            r#"
            UPDATE purchase_orders
            SET status = 'cancelled', updated_at = NOW()
            WHERE id = $1
              AND status NOT IN ('received', 'cancelled')
            RETURNING *
            "#,
        )
        .bind(id)
        .fetch_optional(pool)
        .await
        .map_err(AppError::Database)?
        .ok_or_else(|| {
            AppError::Validation("Purchase order cannot be cancelled in its current state".into())
        })?;

        Ok(result)
    }
}
