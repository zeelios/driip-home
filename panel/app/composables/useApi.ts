export const useApi = <T>(
  path: string | (() => string),
  options: any = {}
) => {
  const config = useRuntimeConfig();
  const route = useRoute();
  
  return $fetch<T>(path, {
    baseUrl: config.public.apiBaseUrl 
  })
}
