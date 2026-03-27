import { ref, computed } from "vue";
import { defineStore } from "pinia";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage, sanitizeString } from "~/utils/format";
import type { ProductModel } from "~~/types/generated/backend-models.generated";
import type {
  CreateProductDto,
  UpdateProductDto,
} from "~~/types/backend-contracts.generated";

type LoadState = "idle" | "loading" | "loaded" | "error";

interface ListMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface ProductListResponse {
  data: ProductModel[];
  meta: ListMeta;
}

interface ProductDetailResponse {
  data: ProductModel;
}

interface ProductFilters {
  search: string;
  status: string;
  page: number;
  per_page: number;
}

interface ProductVariantSearchResult {
  id: string;
  product_id: string;
  sku: string;
  name: string;
  variant_name: string | null;
  unit_price: number;
  stock_quantity: number;
  image: string | null;
}

interface ProductSearchResponse {
  data: ProductVariantSearchResult[];
}

export const useProductsStore = defineStore("products", () => {
  const api = useApi();
  const toast = useToast();

  const listState = ref<LoadState>("idle");
  const listError = ref<string | null>(null);
  const products = ref<ProductModel[]>([]);
  const meta = ref<ListMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  });
  const filters = ref<ProductFilters>({
    search: "",
    status: "",
    page: 1,
    per_page: 20,
  });

  const detailState = ref<LoadState>("idle");
  const detailError = ref<string | null>(null);
  const currentProduct = ref<ProductModel | null>(null);

  const formPending = ref(false);
  const deletePending = ref(false);

  const isListLoading = computed(() => listState.value === "loading");
  const isDetailLoading = computed(() => detailState.value === "loading");

  async function fetchProducts(): Promise<void> {
    listState.value = "loading";
    listError.value = null;

    try {
      const f = filters.value;
      const params: Record<string, string> = {
        page: String(f.page),
        per_page: String(f.per_page),
      };
      if (f.search.trim()) params["search"] = f.search.trim();
      if (f.status) params["status"] = f.status;

      const query = new URLSearchParams(params).toString();
      const response = await api.get<ProductListResponse>(`/products?${query}`);
      products.value = response.data ?? [];
      meta.value = response.meta ?? {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
      };
      listState.value = "loaded";
    } catch (error) {
      listState.value = "error";
      listError.value = getErrorMessage(
        error,
        "Không thể tải danh sách sản phẩm."
      );
    }
  }

  async function fetchProduct(id: string): Promise<void> {
    detailState.value = "loading";
    detailError.value = null;
    currentProduct.value = null;

    try {
      const response = await api.get<ProductDetailResponse>(`/products/${id}`);
      currentProduct.value = response.data ?? null;
      detailState.value = "loaded";
    } catch (error) {
      detailState.value = "error";
      detailError.value = getErrorMessage(
        error,
        "Không thể tải thông tin sản phẩm."
      );
    }
  }

  function sanitizeCreateDto(
    input: Partial<CreateProductDto>
  ): CreateProductDto | null {
    const name = sanitizeString(input.name);
    if (!name) return null;

    return {
      name,
      slug: input.slug ? sanitizeString(input.slug) : null,
      brand_id: input.brand_id ? sanitizeString(input.brand_id) : null,
      category_id: input.category_id ? sanitizeString(input.category_id) : null,
      description: input.description ? sanitizeString(input.description) : null,
      short_description: input.short_description
        ? sanitizeString(input.short_description)
        : null,
      sku_base: input.sku_base ? sanitizeString(input.sku_base) : null,
      gender: input.gender ? sanitizeString(input.gender) : null,
      season: input.season ? sanitizeString(input.season) : null,
      tags: Array.isArray(input.tags) ? input.tags : [],
      status: input.status ? sanitizeString(input.status) : "draft",
    };
  }

  function sanitizeUpdateDto(
    input: Partial<UpdateProductDto>
  ): UpdateProductDto {
    const dto: UpdateProductDto = {};
    if (input.name !== undefined) dto.name = sanitizeString(input.name);
    if (input.status !== undefined)
      dto.status = sanitizeString(input.status) || null;
    if (input.description !== undefined)
      dto.description = sanitizeString(input.description) || null;
    if (input.brand_id !== undefined)
      dto.brand_id = input.brand_id ? sanitizeString(input.brand_id) : null;
    if (input.category_id !== undefined)
      dto.category_id = input.category_id
        ? sanitizeString(input.category_id)
        : null;
    if (input.is_featured !== undefined) dto.is_featured = input.is_featured;
    if (input.tags !== undefined)
      dto.tags = Array.isArray(input.tags) ? input.tags : [];
    return dto;
  }

  async function createProduct(
    input: Partial<CreateProductDto>
  ): Promise<string | null> {
    const dto = sanitizeCreateDto(input);
    if (!dto) {
      toast.error("Dữ liệu không hợp lệ", "Tên sản phẩm là bắt buộc.");
      return null;
    }

    formPending.value = true;
    try {
      const response = await api.post<ProductDetailResponse>(
        "/products",
        dto as unknown as Record<string, unknown>
      );
      toast.success("Đã tạo sản phẩm");
      await fetchProducts();
      return response.data?.id ?? null;
    } catch (error) {
      toast.error("Tạo thất bại", getErrorMessage(error));
      return null;
    } finally {
      formPending.value = false;
    }
  }

  async function updateProduct(
    id: string,
    input: Partial<UpdateProductDto>
  ): Promise<boolean> {
    const dto = sanitizeUpdateDto(input);
    formPending.value = true;

    try {
      await api.put(
        `/products/${id}`,
        dto as unknown as Record<string, unknown>
      );
      toast.success("Đã cập nhật sản phẩm");
      await fetchProduct(id);
      return true;
    } catch (error) {
      toast.error("Cập nhật thất bại", getErrorMessage(error));
      return false;
    } finally {
      formPending.value = false;
    }
  }

  async function deleteProduct(id: string): Promise<boolean> {
    deletePending.value = true;
    try {
      await api.delete(`/products/${id}`);
      toast.success("Đã xóa sản phẩm");
      products.value = products.value.filter((p) => p.id !== id);
      meta.value = { ...meta.value, total: Math.max(0, meta.value.total - 1) };
      return true;
    } catch (error) {
      toast.error("Xóa thất bại", getErrorMessage(error));
      return false;
    } finally {
      deletePending.value = false;
    }
  }

  function setPage(page: number): void {
    filters.value = { ...filters.value, page };
  }

  function setFilters(partial: Partial<Omit<ProductFilters, "page">>): void {
    filters.value = { ...filters.value, ...partial, page: 1 };
  }

  function resetFilters(): void {
    filters.value = { search: "", status: "", page: 1, per_page: 20 };
  }

  async function searchProductsUnified(
    query: string,
    limit: number = 10
  ): Promise<ProductVariantSearchResult[]> {
    if (!query.trim()) return [];

    try {
      const params = new URLSearchParams({
        q: query.trim(),
        limit: String(limit),
      });
      const response = await api.get<ProductSearchResponse>(
        `/products/search?${params.toString()}`
      );
      return response.data ?? [];
    } catch (error) {
      // Silently fail - user can retry by typing
      return [];
    }
  }

  return {
    listState,
    listError,
    products,
    meta,
    filters,
    detailState,
    detailError,
    currentProduct,
    formPending,
    deletePending,
    isListLoading,
    isDetailLoading,
    fetchProducts,
    fetchProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    setPage,
    setFilters,
    resetFilters,
    searchProductsUnified,
  };
});
