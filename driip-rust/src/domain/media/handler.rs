use axum::{
    extract::{Multipart, Path, Query, State},
    http::StatusCode,
    response::{IntoResponse, Redirect},
    Json,
};
use uuid::Uuid;

use crate::{
    auth::{check_permission, AuthContext, Permission},
    errors::AppError,
    state::AppState,
};

use super::{
    model::{
        AttachMediaRequest, AttachMediaToEntityRequest, BucketFileFilter, DetachMediaRequest,
        MediaListFilter, MediaListResponse, MediaResponse, MediaWithRelations, UploadQuery,
    },
    repository::MediaRepository,
    service::image_utils,
};

/// Upload media file (auto-creates thumbnail for images)
pub async fn upload(
    State(state): State<AppState>,
    ctx: AuthContext,
    Query(params): Query<UploadQuery>,
    mut multipart: Multipart,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductUpdate)?;

    // Get B2 client
    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    // Process multipart form
    let field = multipart
        .next_field()
        .await
        .map_err(|e| AppError::Validation(format!("Upload error: {}", e)))?
        .ok_or_else(|| AppError::Validation("No file uploaded".into()))?;

    let filename = field.file_name().unwrap_or("unknown").to_string();
    let content_type = field
        .content_type()
        .unwrap_or("application/octet-stream")
        .to_string();
    let data = field
        .bytes()
        .await
        .map_err(|e| AppError::Validation(format!("Read error: {}", e)))?;

    let size_bytes = data.len() as i64;

    // Determine folder path
    let folder = if params.product_id.is_some() {
        "products"
    } else {
        "uploads"
    };
    let file_path = format!("{}/{}_{}", folder, Uuid::new_v4(), filename);

    // Get image dimensions if it's an image
    let (width, height) = if content_type.starts_with("image/") {
        let dims = image_utils::get_dimensions(&data);
        (dims.map(|d| d.0), dims.map(|d| d.1))
    } else {
        (None, None)
    };

    // Upload to B2 (with thumbnail for images)
    let generate_thumb = content_type.starts_with("image/");
    let (b2_file_id, _, thumb_info) = b2
        .upload(&file_path, data.to_vec(), &content_type, generate_thumb)
        .await
        .map_err(|e| AppError::Internal(format!("B2 upload failed: {}", e)))?;

    // Extract thumbnail info
    let (thumb_path, thumb_w, thumb_h) = match thumb_info {
        Some((_, path, w, h)) => (Some(path), Some(w), Some(h)),
        _ => (None, None, None),
    };

    // Save to our media map
    let media = MediaRepository::create(
        &state.db,
        &filename,
        &file_path,
        thumb_path.as_deref(),
        &content_type,
        size_bytes,
        width.or(thumb_w),
        height.or(thumb_h),
        Some(&b2_file_id),
        Some(ctx.staff_id),
    )
    .await?;

    // Attach to product if requested
    if let Some(product_id) = params.product_id {
        let is_primary = params.is_primary.unwrap_or(false);
        MediaRepository::attach_to_product(&state.db, product_id, media.id, is_primary, 0).await?;
    }

    // Build response with URLs
    let response = MediaResponse {
        id: media.id,
        filename: media.filename,
        url: b2.get_url(&media.original_path),
        thumbnail_url: media
            .thumbnail_path
            .as_ref()
            .map(|p| b2.get_thumbnail_url(p)),
        width: media.width,
        height: media.height,
        size_bytes: media.size_bytes,
        mime_type: media.mime_type,
        is_primary: params.is_primary.unwrap_or(false),
        sort_order: 0,
    };

    Ok((StatusCode::CREATED, Json(response)))
}

/// Get media details (from our map, no B2 API call)
pub async fn get(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let media = MediaRepository::find_by_id(&state.db, id)
        .await?
        .ok_or_else(|| AppError::NotFound("Media not found".into()))?;

    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    let response = MediaResponse {
        id: media.id,
        filename: media.filename,
        url: b2.get_url(&media.original_path),
        thumbnail_url: media
            .thumbnail_path
            .as_ref()
            .map(|p| b2.get_thumbnail_url(p)),
        width: media.width,
        height: media.height,
        size_bytes: media.size_bytes,
        mime_type: media.mime_type,
        is_primary: false,
        sort_order: 0,
    };

    Ok(Json(response))
}

/// List media for a product (from our map, fast, no B2 API calls)
pub async fn list_by_product(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Path(product_id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let items = MediaRepository::list_by_product(&state.db, product_id).await?;

    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    let responses: Vec<MediaResponse> = items
        .into_iter()
        .map(|m| MediaResponse {
            id: m.id,
            filename: m.filename,
            url: b2.get_url(&m.original_path),
            thumbnail_url: m.thumbnail_path.as_ref().map(|p| b2.get_url(p)),
            width: m.width,
            height: m.height,
            size_bytes: m.size_bytes,
            mime_type: m.mime_type,
            is_primary: m.is_primary,
            sort_order: m.sort_order,
        })
        .collect();

    Ok(Json(responses))
}

/// Attach existing media to product
pub async fn attach_to_product(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(product_id): Path<Uuid>,
    Json(req): Json<AttachMediaRequest>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductUpdate)?;

    MediaRepository::attach_to_product(
        &state.db,
        product_id,
        req.media_id,
        req.is_primary.unwrap_or(false),
        req.sort_order.unwrap_or(0),
    )
    .await?;

    Ok(StatusCode::OK)
}

/// Delete media (removes from B2 and our map)
pub async fn delete(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductDelete)?;

    // Get B2 file ID for deletion
    let b2_file_id = MediaRepository::get_b2_file_id(&state.db, id).await?;

    // Delete from B2 if we have the file ID
    if let Some(file_id) = b2_file_id {
        let b2 = state.b2.clone();
        if let Some(client) = b2 {
            // Spawn blocking or fire-and-forget B2 deletion
            tokio::spawn(async move {
                let _ = client.delete(&file_id).await;
            });
        }
    }

    // Delete from our map
    MediaRepository::delete(&state.db, id).await?;

    Ok(StatusCode::NO_CONTENT)
}

/// Serve original file (redirects to B2/CDN)
pub async fn serve(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let media = MediaRepository::find_by_id(&state.db, id)
        .await?
        .ok_or_else(|| AppError::NotFound("Media not found".into()))?;

    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    let url = b2.get_url(&media.original_path);
    Ok(Redirect::temporary(&url))
}

/// Serve thumbnail (redirects to B2/CDN)
pub async fn serve_thumbnail(
    State(state): State<AppState>,
    Path(id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let media = MediaRepository::find_by_id(&state.db, id)
        .await?
        .ok_or_else(|| AppError::NotFound("Media not found".into()))?;

    let thumb_path = media
        .thumbnail_path
        .ok_or_else(|| AppError::NotFound("No thumbnail".into()))?;

    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    let url = b2.get_thumbnail_url(&thumb_path);
    Ok(Redirect::temporary(&url))
}

// ═════════════════════════════════════════════════════════════════════════════
// NEW: Media Relations (Polymorphic)
// ═════════════════════════════════════════════════════════════════════════════

/// Attach media to any entity (polymorphic)
pub async fn attach_to_entity(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(media_id): Path<Uuid>,
    Json(req): Json<AttachMediaToEntityRequest>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductUpdate)?;

    let relation = MediaRepository::attach_to_entity(
        &state.db,
        media_id,
        &req.entity_type,
        req.entity_id,
        req.is_primary.unwrap_or(false),
        req.sort_order.unwrap_or(0),
    )
    .await?;

    Ok((StatusCode::OK, Json(relation)))
}

/// Detach media from an entity
pub async fn detach_from_entity(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(media_id): Path<Uuid>,
    Json(req): Json<DetachMediaRequest>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductUpdate)?;

    MediaRepository::detach_from_entity(&state.db, media_id, &req.entity_type, req.entity_id)
        .await?;

    Ok(StatusCode::NO_CONTENT)
}

/// Get all relations for a media
pub async fn get_media_relations(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Path(media_id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let relations = MediaRepository::get_media_relations(&state.db, media_id).await?;
    Ok(Json(relations))
}

/// Get media for any entity (polymorphic)
pub async fn get_media_for_entity(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Path(entity_type): Path<String>,
    Path(entity_id): Path<Uuid>,
) -> Result<impl IntoResponse, AppError> {
    let items = MediaRepository::get_media_for_entity(&state.db, &entity_type, entity_id).await?;

    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    let responses: Vec<MediaResponse> = items
        .into_iter()
        .map(|m| MediaResponse {
            id: m.id,
            filename: m.filename,
            url: b2.get_url(&m.original_path),
            thumbnail_url: m.thumbnail_path.as_ref().map(|p| b2.get_url(p)),
            width: m.width,
            height: m.height,
            size_bytes: m.size_bytes,
            mime_type: m.mime_type,
            is_primary: m.is_primary,
            sort_order: m.sort_order,
        })
        .collect();

    Ok(Json(responses))
}

/// List all media with filters and pagination
pub async fn list_media(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Query(filter): Query<MediaListFilter>,
) -> Result<impl IntoResponse, AppError> {
    let limit = filter.limit.unwrap_or(50);
    let offset = filter.offset.unwrap_or(0);

    let (media, total) = MediaRepository::list_media_with_relations(
        &state.db,
        filter.entity_type.as_deref(),
        filter.entity_id,
        filter.has_relations,
        filter.uploaded_by,
        limit,
        offset,
    )
    .await?;

    let _b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    // For each media, fetch its relations
    let mut media_with_relations = Vec::new();
    for m in media {
        let relations = MediaRepository::get_media_relations(&state.db, m.id).await?;
        media_with_relations.push(MediaWithRelations {
            media: m,
            relations,
        });
    }

    let response = MediaListResponse {
        data: media_with_relations,
        total,
        limit,
        offset,
    };

    Ok(Json(response))
}

// ═════════════════════════════════════════════════════════════════════════════
// NEW: B2 Bucket File Browsing
// ═════════════════════════════════════════════════════════════════════════════

/// List files in B2 bucket (with optional prefix for directory browsing)
pub async fn list_bucket_files(
    State(state): State<AppState>,
    _ctx: AuthContext,
    Query(filter): Query<BucketFileFilter>,
) -> Result<impl IntoResponse, AppError> {
    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    let prefix = filter.prefix.as_deref();
    let limit = filter.limit.unwrap_or(100);

    // Access the list_files method through the service module
    let files = b2
        .list_files(prefix, limit, None)
        .await
        .map_err(|e| AppError::Internal(format!("B2 list files failed: {}", e)))?;

    // Map to the response type
    let response: Vec<super::model::B2BucketFile> = files
        .into_iter()
        .map(|f| super::model::B2BucketFile {
            file_id: f.file_id,
            file_name: f.file_name,
            content_type: f.content_type,
            size: f.size,
            uploaded_at: f.uploaded_at,
            is_thumbnail: f.is_thumbnail,
        })
        .collect();

    Ok(Json(response))
}

/// Delete file from B2 bucket (admin only)
pub async fn delete_bucket_file(
    State(state): State<AppState>,
    ctx: AuthContext,
    Path(file_id): Path<String>,
) -> Result<impl IntoResponse, AppError> {
    check_permission(&ctx, Permission::ProductDelete)?;

    let b2 = state
        .b2
        .clone()
        .ok_or_else(|| AppError::Internal("B2 not configured".into()))?;

    b2.delete(&file_id)
        .await
        .map_err(|e| AppError::Internal(format!("B2 delete failed: {}", e)))?;

    Ok(StatusCode::NO_CONTENT)
}
