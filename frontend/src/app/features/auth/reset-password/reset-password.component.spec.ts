import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, provideRouter, Router } from '@angular/router';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/auth/auth.service';
import { ResetPasswordComponent } from './reset-password.component';

describe('ResetPasswordComponent', () => {
  let fixture: ComponentFixture<ResetPasswordComponent>;
  let auth: Pick<AuthService, 'resetPassword'>;

  beforeEach(() => {
    auth = { resetPassword: vi.fn().mockReturnValue(of({})) };
    TestBed.configureTestingModule({
      imports: [ResetPasswordComponent],
      providers: [
        provideRouter([{ path: 'login', children: [] }]),
        { provide: ActivatedRoute, useValue: { snapshot: { queryParamMap: new URLSearchParams('token=abc') } } },
        { provide: AuthService, useValue: auth },
      ],
    });
    fixture = TestBed.createComponent(ResetPasswordComponent);
    fixture.detectChanges();
  });

  it('checks matching passwords before sending the token and password', async () => {
    fixture.componentInstance.form.setValue({ password: 'clave1234', confirmation: 'otra1234' });
    fixture.componentInstance.submit();
    expect(auth.resetPassword).not.toHaveBeenCalled();
    fixture.componentInstance.form.setValue({ password: 'clave1234', confirmation: 'clave1234' });
    fixture.componentInstance.submit();
    expect(auth.resetPassword).toHaveBeenCalledWith('abc', 'clave1234');
    await fixture.whenStable();
    expect(TestBed.inject(Router).url).toContain('reset=success');
    expect((fixture.nativeElement as HTMLElement).querySelector('.auth-split')).not.toBeNull();
  });
});
