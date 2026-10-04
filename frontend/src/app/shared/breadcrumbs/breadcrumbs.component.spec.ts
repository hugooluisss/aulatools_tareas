import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { BreadcrumbsComponent } from './breadcrumbs.component';

describe('BreadcrumbsComponent', () => {
  it('renders an accessible ordered path and marks the current page', () => {
    TestBed.configureTestingModule({
      imports: [BreadcrumbsComponent],
      providers: [provideRouter([])],
    });
    const fixture = TestBed.createComponent(BreadcrumbsComponent);
    fixture.componentRef.setInput('items', [
      { label: 'Mis materias', url: '/my-subjects' },
      { label: 'Ciencias' },
    ]);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('nav[aria-label="Migas de pan"]')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('ol')).not.toBeNull();
    expect(fixture.nativeElement.querySelector('a').textContent).toContain('Mis materias');
    expect(fixture.nativeElement.querySelector('[aria-current="page"]').textContent).toContain(
      'Ciencias',
    );
  });
});
