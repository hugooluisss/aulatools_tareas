import { ComponentFixture, TestBed } from '@angular/core/testing';
import { IconButtonComponent } from './icon-button.component';
import { Tooltip } from 'bootstrap';
import { vi } from 'vitest';

describe('IconButtonComponent', () => {
  let fixture: ComponentFixture<IconButtonComponent>;
  beforeEach(() => {
    vi.spyOn(Tooltip.prototype, 'dispose');
    TestBed.configureTestingModule({ imports: [IconButtonComponent] });
    fixture = TestBed.createComponent(IconButtonComponent);
    fixture.componentInstance.icon = 'pencil';
    fixture.componentInstance.label = 'Editar';
    fixture.detectChanges();
  });
  it('renders an icon button with an accessible label and tooltip', () => {
    const button: HTMLButtonElement = fixture.nativeElement.querySelector('button');
    expect(button.getAttribute('aria-label')).toBe('Editar');
    expect(button.getAttribute('data-bs-title')).toBe('Editar');
    expect(button.querySelector('i')?.getAttribute('class')).toBe('bi bi-pencil');
  });

  it('disables the native button when requested', () => {
    fixture.componentRef.setInput('disabled', true);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('button').disabled).toBe(true);
  });
});
