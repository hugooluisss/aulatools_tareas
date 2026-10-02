import { AfterViewInit, Directive, ElementRef, Input, OnDestroy, inject } from '@angular/core';
import { Tooltip } from 'bootstrap';
import type { Tooltip as BootstrapTooltip } from 'bootstrap';

@Directive({ selector: '[appTooltip]', standalone: true })
export class AppTooltipDirective implements AfterViewInit, OnDestroy {
  @Input('appTooltip') label = '';
  private readonly element = inject(ElementRef<HTMLElement>);
  private tooltip?: BootstrapTooltip;
  ngAfterViewInit(): void {
    this.element.nativeElement.setAttribute('data-bs-title', this.label);
    this.tooltip = new Tooltip(this.element.nativeElement);
  }
  ngOnDestroy(): void {
    this.tooltip?.dispose();
  }
}
