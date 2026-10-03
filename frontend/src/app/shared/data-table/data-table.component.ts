import { CommonModule } from '@angular/common';
import { Component, contentChildren, input, output } from '@angular/core';
import { Page } from '../../core/models/page';
import { PaginatorComponent } from '../paginator/paginator.component';
import { ColumnComponent } from './column.component';

@Component({
  selector: 'app-data-table',
  standalone: true,
  imports: [CommonModule, PaginatorComponent],
  templateUrl: './data-table.component.html',
})
export class DataTableComponent<T> {
  page = input.required<Page<T>>();
  loading = input(false);
  pageChange = output<number>();
  columns = contentChildren(ColumnComponent);
}
