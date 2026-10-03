import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/auth/auth.service';
import { ForgotPasswordComponent } from './forgot-password.component';

describe('ForgotPasswordComponent', () => {
  let fixture: ComponentFixture<ForgotPasswordComponent>;
  let auth: Pick<AuthService, 'forgotPassword'>;

  beforeEach(() => {
    auth = { forgotPassword: vi.fn().mockReturnValue(of({ message: 'ok' })) };
    TestBed.configureTestingModule({
      imports: [ForgotPasswordComponent],
      providers: [provideRouter([]), { provide: AuthService, useValue: auth }],
    });
    fixture = TestBed.createComponent(ForgotPasswordComponent);
    fixture.detectChanges();
  });

  it('submits a valid email and displays the generic confirmation', () => {
    fixture.componentInstance.form.setValue({ email: 'a@escuela.mx' });
    fixture.componentInstance.submit();
    expect(auth.forgotPassword).toHaveBeenCalledWith('a@escuela.mx');
    expect(fixture.componentInstance.submitted()).toBe(true);
    fixture.detectChanges();
    expect((fixture.nativeElement as HTMLElement).textContent).toContain('Si el correo está registrado');
  });
});
