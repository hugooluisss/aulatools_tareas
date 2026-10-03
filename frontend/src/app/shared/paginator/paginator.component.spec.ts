import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PaginatorComponent } from './paginator.component';

describe('PaginatorComponent', () => {
  let fixture: ComponentFixture<PaginatorComponent>;
  beforeEach(() => {
    TestBed.configureTestingModule({ imports: [PaginatorComponent] });
    fixture = TestBed.createComponent(PaginatorComponent);
    fixture.componentRef.setInput('page', 5);
    fixture.componentRef.setInput('totalPages', 12);
    fixture.componentRef.setInput('total', 240);
    fixture.detectChanges();
  });

  it('renders accessible page buttons with ellipses', () => {
    const nav: HTMLElement = fixture.nativeElement.querySelector('nav');
    expect(nav.getAttribute('aria-label')).toBe('Paginación');
    expect(nav.querySelector('[aria-current="page"]')?.textContent?.trim()).toBe('5');
    expect(nav.textContent).toContain('…');
  });

  it('emits a selected page', () => {
    const emitted: number[] = [];
    fixture.componentInstance.pageChange.subscribe((page) => emitted.push(page));
    fixture.nativeElement.querySelectorAll('.page-link')[4].click();
    expect(emitted).toEqual([6]);
  });
});
