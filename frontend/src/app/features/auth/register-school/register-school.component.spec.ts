import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router, Routes } from '@angular/router';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/auth/auth.service';
import { RegisterSchoolComponent } from './register-school.component';

describe('RegisterSchoolComponent', () => {
  let fixture: ComponentFixture<RegisterSchoolComponent>;
  let auth: Pick<AuthService, 'registerSchool'>;
  beforeEach(() => {
    auth = { registerSchool: vi.fn().mockReturnValue(of({})) };
    TestBed.configureTestingModule({
      imports: [RegisterSchoolComponent],
      providers: [
        provideRouter([{ path: 'login', children: [] }] as Routes),
        { provide: AuthService, useValue: auth },
      ],
    });
    fixture = TestBed.createComponent(RegisterSchoolComponent);
    fixture.detectChanges();
  });
  it('validates required fields, email and password length', () => {
    fixture.componentInstance.submit();
    expect(auth.registerSchool).not.toHaveBeenCalled();
    fixture.componentInstance.form.setValue({
      school_name: 'Escuela',
      first_name: 'Ana',
      last_name: 'Ruiz',
      email: 'mal',
      password: '123',
    });
    expect(fixture.componentInstance.form.invalid).toBe(true);
  });
  it('submits registration data and navigates to login', async () => {
    const data = {
      school_name: 'Escuela',
      first_name: 'Ana',
      last_name: 'Ruiz',
      email: 'ana@escuela.mx',
      password: 'clave1234',
    };
    fixture.componentInstance.form.setValue(data);
    fixture.componentInstance.submit();
    expect(auth.registerSchool).toHaveBeenCalledWith(data);
    await fixture.whenStable();
    expect(TestBed.inject(Router).url).toBe('/login');
  });
});
