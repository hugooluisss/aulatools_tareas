import { Component, computed, input, output } from '@angular/core';
import { IconButtonComponent } from '../icon-button/icon-button.component';

@Component({
  selector: 'app-paginator',
  standalone: true,
  imports: [IconButtonComponent],
  templateUrl: './paginator.component.html',
})
export class PaginatorComponent {
  page = input.required<number>();
  totalPages = input.required<number>();
  total = input.required<number>();
  pageChange = output<number>();

  pages = computed(() => {
    const total = this.totalPages();
    const current = this.page();
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const start = Math.max(2, Math.min(current - 1, total - 3));
    const end = Math.min(total - 1, start + 2);
    return [1, ...(start > 2 ? ['…'] : []), ...Array.from({ length: end - start + 1 }, (_, i) => start + i), ...(end < total - 1 ? ['…'] : []), total];
  });

  select(page: number | string): void {
    if (typeof page === 'number' && page !== this.page()) this.pageChange.emit(page);
  }
}
