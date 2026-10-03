import { Component, signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Page } from '../../core/models/page';
import { ColumnComponent } from './column.component';
import { DataTableComponent } from './data-table.component';

interface Row { name: string }

@Component({
  standalone: true,
  imports: [DataTableComponent, ColumnComponent],
  template: `<app-data-table [page]="page()"><app-column header="Nombre"><ng-template let-row>{{ row.name }} <button type="button">Abrir</button></ng-template></app-column></app-data-table>`,
})
class TableHostComponent {
  page = signal<Page<Row>>({ items: [{ name: 'Ana' }], page: 1, per_page: 20, total: 1, total_pages: 1 });
}

describe('DataTableComponent', () => {
  let fixture: ComponentFixture<TableHostComponent>;
  beforeEach(() => {
    TestBed.configureTestingModule({ imports: [TableHostComponent] });
    fixture = TestBed.createComponent(TableHostComponent);
    fixture.detectChanges();
  });

  it('projects column headers and renders cell content inside table cells', () => {
    expect(fixture.nativeElement.querySelector('th')?.textContent.trim()).toBe('Nombre');
    expect(fixture.nativeElement.querySelector('td')?.textContent).toContain('Ana');
    expect(fixture.nativeElement.querySelector('td button')).not.toBeNull();
  });

  it('shows the empty state without a paginator', () => {
    fixture.componentInstance.page.update((page) => ({ ...page, items: [], total: 0, total_pages: 1 }));
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('No hay registros');
    expect(fixture.nativeElement.querySelector('app-paginator')).toBeNull();
  });
});
