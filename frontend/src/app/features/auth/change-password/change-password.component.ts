import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService } from '../../../core/auth/auth.service';

@Component({
  selector: 'app-change-password',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './change-password.component.html',
  styleUrl: './change-password.component.scss',
})
export class ChangePasswordComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  form = this.fb.nonNullable.group({
    current_password: ['', Validators.required],
    new_password: ['', [Validators.required, Validators.minLength(8)]],
  });
  message = '';
  submit(): void {
    if (this.form.invalid) return;
    this.auth.changePassword(this.form.getRawValue()).subscribe({
      next: () => (this.message = 'Contraseña actualizada.'),
      error: () => (this.message = 'No se pudo cambiar la contraseña.'),
    });
  }
}
