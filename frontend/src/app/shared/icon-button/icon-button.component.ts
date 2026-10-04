import { Component, Input, output } from '@angular/core';
import { AppTooltipDirective } from '../tooltip/app-tooltip.directive';

@Component({
  selector: 'app-icon-button',
  standalone: true,
  imports: [AppTooltipDirective],
  templateUrl: './icon-button.component.html',
  styleUrl: './icon-button.component.scss',
})
export class IconButtonComponent {
  clicked = output<void>();
  @Input({ required: true }) icon = '';
  @Input({ required: true }) label = '';
  @Input() variant = 'secondary';
  @Input() disabled = false;
  @Input() badge = 0;

  get buttonClass(): string {
    return `btn btn-${this.variant}`;
  }

  get iconClass(): string {
    return `bi bi-${this.icon}`;
  }
}
