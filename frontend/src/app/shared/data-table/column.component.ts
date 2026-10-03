import { Component, TemplateRef, contentChild, input } from '@angular/core';

@Component({ selector: 'app-column', standalone: true, template: '', host: { style: 'display: none' } })
export class ColumnComponent {
  header = input.required<string>();
  cell = contentChild.required<TemplateRef<{ $implicit: unknown }>>(TemplateRef);
}
