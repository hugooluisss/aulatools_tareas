export interface Page<T> {
  items: T[];
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}
