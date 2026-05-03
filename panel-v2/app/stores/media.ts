import { defineStore } from "pinia";

export interface Media {
  id: string;
  filename: string;
  url: string;
  thumbnail_url?: string;
  width?: number;
  height?: number;
  size_bytes: number;
  mime_type: string;
  is_primary: boolean;
  sort_order: number;
}

export const useMediaStore = defineStore("media", {
  state: () => ({
    uploading: false,
    uploadProgress: 0,
    error: null as string | null,
  }),

  actions: {
    /**
     * Upload file to B2 storage with auto-thumbnail generation
     * @param file - The file to upload
     * @param productId - Optional product ID to attach immediately
     * @param isPrimary - Whether this should be the primary image
     */
    async upload(
      file: File,
      productId?: string,
      isPrimary = false
    ): Promise<Media | null> {
      this.uploading = true;
      this.error = null;
      this.uploadProgress = 0;

      try {
        const { post } = useApi();
        const formData = new FormData();
        formData.append("file", file);

        // Build query params
        const params = new URLSearchParams();
        if (productId) params.append("product_id", productId);
        if (isPrimary) params.append("is_primary", "true");

        const query = params.toString();
        const path = query ? `/media/upload?${query}` : "/media/upload";

        const media = await post<Media>(path, formData);
        return media;
      } catch (e: any) {
        this.error = e?.data?.message || "Upload failed";
        return null;
      } finally {
        this.uploading = false;
      }
    },

    /**
     * Get list of media for a product
     */
    async listByProduct(productId: string): Promise<Media[]> {
      try {
        const { get } = useApi();
        const items = await get<Media[]>(`/media/products/${productId}`);
        return items;
      } catch (e: any) {
        this.error = e?.data?.message || "Failed to load media";
        return [];
      }
    },

    /**
     * Attach existing media to product
     */
    async attachToProduct(
      productId: string,
      mediaId: string,
      isPrimary = false,
      sortOrder = 0
    ): Promise<boolean> {
      try {
        const { post } = useApi();
        await post(`/media/products/${productId}`, {
          media_id: mediaId,
          is_primary: isPrimary,
          sort_order: sortOrder,
        });
        return true;
      } catch (e: any) {
        this.error = e?.data?.message || "Failed to attach media";
        return false;
      }
    },

    /**
     * Delete media (removes from B2 and local map)
     */
    async delete(mediaId: string): Promise<boolean> {
      try {
        const { del } = useApi();
        await del(`/media/${mediaId}`);
        return true;
      } catch (e: any) {
        this.error = e?.data?.message || "Failed to delete media";
        return false;
      }
    },
  },
});
