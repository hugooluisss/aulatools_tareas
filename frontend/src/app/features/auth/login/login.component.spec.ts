import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router, Routes } from '@angular/router';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/auth/auth.service';
import { LoginComponent } from './login.component';

describe('LoginComponent', () => {
  let fixture: ComponentFixture<LoginComponent>;
  let auth: Pick<AuthService, 'login'>;
  beforeEach(() => {
    auth = { login: vi.fn().mockReturnValue(of({ token: 'jwt' })) };
    TestBed.configureTestingModule({
      imports: [LoginComponent],
      providers: [
        provideRouter([{ path: 'inicio', children: [] }] as Routes),
        { provide: AuthService, useValue: auth },
      ],
    });
    fixture = TestBed.createComponent(LoginComponent);
    fixture.detectChanges();
  });
  it('validates email and password before submitting', () => {
    fixture.componentInstance.submit();
    expect(fixture.componentInstance.form.invalid).toBe(true);
    expect(auth.login).not.toHaveBeenCalled();
    fixture.componentInstance.form.setValue({ email: 'bad', password: 'x' });
    expect(fixture.componentInstance.form.invalid).toBe(true);
  });
  it('calls AuthService and navigates after a valid login', async () => {
    fixture.componentInstance.form.setValue({ email: 'admin@escuela.mx', password: 'secreto' });
    fixture.componentInstance.submit();
    expect(auth.login).toHaveBeenCalledWith({ email: 'admin@escuela.mx', password: 'secreto' });
    await fixture.whenStable();
    expect(TestBed.inject(Router).url).toBe('/inicio');
  });
});
