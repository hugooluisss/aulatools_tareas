import { Component, input, output } from '@angular/core';
import { TaskDeliveryStatus } from '../../features/tasks/tasks.service';

@Component({
  selector: 'app-status-filter',
  standalone: true,
  template: `
    <div class="status-filter" role="group" aria-label="Filtrar por estado">
      @for (status of statuses(); track status.code) {
        <button
          type="button"
          class="status-filter__button"
          [class.status-filter__button--selected]="selected().includes(status.code)"
          [style.--status-color]="status.color"
          [style.--status-text-color]="status.text_color"
          [attr.aria-pressed]="selected().includes(status.code)"
          (click)="toggle.emit(status.code)"
        >
          {{ status.label }}
        </button>
      }
    </div>
  `,
  styleUrl: './status-filter.component.scss',
})
export class StatusFilterComponent {
  readonly statuses = input.required<TaskDeliveryStatus[]>();
  readonly selected = input.required<string[]>();
  readonly toggle = output<string>();
}
