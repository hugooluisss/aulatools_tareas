import { Component, input } from '@angular/core';
import { TaskDeliveryStatus } from '../../features/tasks/tasks.service';

@Component({
  selector: 'app-status-badge',
  standalone: true,
  template: `
    @if (status(); as value) {
      <span
        class="status-badge"
        [style.--status-color]="value.color"
        [style.--status-text-color]="value.text_color"
        >{{ value.label }}</span
      >
    }
  `,
  styleUrl: './status-badge.component.scss',
})
export class StatusBadgeComponent {
  readonly status = input<TaskDeliveryStatus | undefined>();
}
