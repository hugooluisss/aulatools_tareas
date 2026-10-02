import { Component } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AppTooltipDirective } from './app-tooltip.directive';
import { Tooltip } from 'bootstrap';
import { vi } from 'vitest';

@Component({
  standalone: true,
  imports: [AppTooltipDirective],
  template: '<button appTooltip="Guardar cambios">Acción</button>',
})
class TooltipHostComponent {}

describe('AppTooltipDirective', () => {
  let fixture: ComponentFixture<TooltipHostComponent>;
  beforeEach(() => {
    vi.spyOn(Tooltip.prototype, 'dispose');
    TestBed.configureTestingModule({ imports: [TooltipHostComponent] });
    fixture = TestBed.createComponent(TooltipHostComponent);
    fixture.detectChanges();
  });
  it('initializes Bootstrap and disposes the tooltip on destroy', () => {
    const button: HTMLButtonElement = fixture.nativeElement.querySelector('button');
    expect(button.getAttribute('data-bs-title')).toBe('Guardar cambios');
    fixture.destroy();
    expect(Tooltip.prototype.dispose).toHaveBeenCalled();
  });
});
