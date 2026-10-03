import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth/auth.service';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './reset-password.component.html',
  styleUrl: './reset-password.component.scss',
})
export class ResetPasswordComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly token = inject(ActivatedRoute).snapshot.queryParamMap.get('token') ?? '';
  form = this.fb.nonNullable.group({
    password: ['', [Validators.required, Validators.minLength(8)]],
    confirmation: ['', Validators.required],
  });
  error = signal('');

  submit(): void {
    if (!this.token) return this.error.set('El enlace no es válido o ya expiró.');
    if (this.form.invalid) return;
    const { password, confirmation } = this.form.getRawValue();
    if (password !== confirmation) return this.error.set('Las contraseñas no coinciden.');
    this.auth.resetPassword(this.token, password).subscribe({
      next: () => this.router.navigate(['/login'], { queryParams: { reset: 'success' } }),
      error: (response: { error?: { message?: string } }) =>
        this.error.set(response.error?.message ?? 'El enlace no es válido o ya expiró.'),
    });
  }
}
