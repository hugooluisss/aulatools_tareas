import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { SchoolService } from '../school.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-school',
  standalone: true,
  imports: [ReactiveFormsModule],
  template: `
    <section class="card p-4">
      <h1>Escuela</h1>
      <form class="row g-3" [formGroup]="form" (ngSubmit)="save()">
        <div class="col-md-8">
          <label class="form-label" for="school-name">Nombre de la escuela</label>
          <input id="school-name" class="form-control" formControlName="name" required />
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit" [disabled]="form.invalid">
            Guardar cambios
          </button>
        </div>
      </form>
    </section>
  `,
})
export class SchoolComponent {
  private readonly api = inject(SchoolService);
  private readonly toast = inject(ToastService);
  form = inject(FormBuilder).nonNullable.group({ name: ['', Validators.required] });
  constructor() {
    this.api.get().subscribe((r) => this.form.patchValue(r));
  }
  save(): void {
    this.api.update(this.form.getRawValue().name).subscribe(() => {
      this.toast.show('Escuela actualizada.');
      location.reload(); // refresca el nombre mostrado en el menú
    });
  }
}
