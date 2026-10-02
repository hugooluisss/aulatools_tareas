import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/auth/auth.service';
import { ChangePasswordComponent } from './change-password.component';

describe('ChangePasswordComponent', () => {
  let fixture: ComponentFixture<ChangePasswordComponent>;
  let auth: Pick<AuthService, 'changePassword'>;
  beforeEach(() => {
    auth = { changePassword: vi.fn().mockReturnValue(of({})) };
    TestBed.configureTestingModule({
      imports: [ChangePasswordComponent],
      providers: [{ provide: AuthService, useValue: auth }],
    });
    fixture = TestBed.createComponent(ChangePasswordComponent);
    fixture.detectChanges();
  });
  it('requires the current password and a new password of at least eight characters', () => {
    fixture.componentInstance.submit();
    expect(auth.changePassword).not.toHaveBeenCalled();
    fixture.componentInstance.form.setValue({ current_password: 'old', new_password: 'short' });
    expect(fixture.componentInstance.form.invalid).toBe(true);
  });
  it('sends a valid password change to AuthService', () => {
    const data = { current_password: 'oldpass', new_password: 'newpass123' };
    fixture.componentInstance.form.setValue(data);
    fixture.componentInstance.submit();
    expect(auth.changePassword).toHaveBeenCalledWith(data);
    expect(fixture.componentInstance.message).toBe('Contraseña actualizada.');
  });
});
