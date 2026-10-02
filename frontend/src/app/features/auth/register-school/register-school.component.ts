import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth/auth.service';

@Component({
  selector: 'app-register-school',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './register-school.component.html',
  styleUrl: './register-school.component.scss',
})
export class RegisterSchoolComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  form = this.fb.nonNullable.group({
    school_name: ['', Validators.required],
    first_name: ['', Validators.required],
    last_name: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
  });
  error = '';
  submit(): void {
    if (this.form.invalid) return;
    this.auth.registerSchool(this.form.getRawValue()).subscribe({
      next: () => this.router.navigateByUrl('/login'),
      error: () => (this.error = 'No se pudo registrar la escuela.'),
    });
  }
}
