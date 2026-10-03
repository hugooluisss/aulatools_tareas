import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { SchoolService } from '../school.service';
import { ToastService } from '../../../core/services/toast.service';
import { environment } from '../../../../environments/environment';
import { IconButtonComponent } from '../../../shared/icon-button/icon-button.component';

@Component({
  selector: 'app-school',
  standalone: true,
  imports: [ReactiveFormsModule, IconButtonComponent],
  template: `
    <section class="card p-4">
      <h1>Escuela</h1>
      <form class="row g-3" [formGroup]="form" (ngSubmit)="save()">
        <div class="col-md-8">
          <label class="form-label" for="school-name">Nombre de la escuela</label>
          <input id="school-name" class="form-control" formControlName="name" required />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="school-address">Dirección</label>
          <input id="school-address" class="form-control" formControlName="address" />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="school-phone">Teléfono</label>
          <input id="school-phone" class="form-control" type="tel" formControlName="phone" />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="school-email">Correo electrónico</label>
          <input id="school-email" class="form-control" type="email" formControlName="email" />
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit" [disabled]="form.invalid">
            Guardar cambios
          </button>
        </div>
      </form>
      <div class="mt-4">
        <label class="form-label" for="school-logo">Logotipo</label>
        <input id="school-logo" class="form-control" type="file" accept="image/jpeg,image/png,image/webp" (change)="uploadLogo($event)" />
        @if (logoUrl()) {
          <div class="d-flex align-items-center gap-2 mt-2">
            <img [src]="logoUrl()" alt="Logotipo de la escuela" style="max-width: 240px; max-height: 120px; object-fit: contain" />
            <app-icon-button icon="trash" label="Quitar logotipo" variant="danger" (click)="removeLogo()" />
          </div>
        }
      </div>
    </section>
  `,
})
export class SchoolComponent {
  private readonly api = inject(SchoolService);
  private readonly toast = inject(ToastService);
  private logoPath = signal<string | null>(null);
  logoUrl = computed(() => this.logoPath() ? `${environment.apiUrl}${this.logoPath()}` : null);
  form = inject(FormBuilder).nonNullable.group({
    name: ['', Validators.required],
    address: [''],
    phone: [''],
    email: ['', Validators.email],
  });
  constructor() {
    this.api.get().subscribe((r) => {
      this.logoPath.set(r.logo_url);
      this.form.patchValue({
        name: r.name,
        address: r.address ?? '',
        phone: r.phone ?? '',
        email: r.email ?? '',
      });
    });
  }
  save(): void {
    this.api.update(this.form.getRawValue()).subscribe(() => {
      this.toast.show('Escuela actualizada.');
      location.reload(); // refresca el nombre mostrado en el menú
    });
  }
  uploadLogo(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    this.api.uploadLogo(file).subscribe({
      next: (school) => this.logoPath.set(school.logo_url),
      error: (error: Error) => this.toast.show(error.message),
    });
  }
  removeLogo(): void {
    this.api.removeLogo().subscribe({
      next: (school) => this.logoPath.set(school.logo_url),
      error: (error: Error) => this.toast.show(error.message),
    });
  }
}
