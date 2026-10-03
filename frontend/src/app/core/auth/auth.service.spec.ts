import { environment } from '../../../environments/environment';
import { TestBed } from '@angular/core/testing';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { AuthService } from './auth.service';
import { authInterceptor } from './auth.interceptor';

describe('AuthService', () => {
  let service: AuthService;
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
      ],
    });
    service = TestBed.inject(AuthService);
    http = TestBed.inject(HttpTestingController);
    localStorage.clear();
  });
  afterEach(() => http.verify());
  it('sends login and stores returned token', () => {
    service
      .login({ email: 'a@escuela.mx', password: 'clave1234' })
      .subscribe((result) => expect(result.token).toBe('jwt'));
    const request = http.expectOne(`${environment.apiUrl}/auth/login`);
    expect(request.request.method).toBe('POST');
    request.flush({ token: 'jwt' });
    expect(localStorage.getItem('aulatools_token')).toBe('jwt');
  });
  it('adds the stored JWT to authenticated requests', () => {
    localStorage.setItem('aulatools_token', 'jwt');
    service.changePassword({ current_password: 'a', new_password: 'b' }).subscribe();
    const request = http.expectOne(`${environment.apiUrl}/auth/change-password`);
    expect(request.request.headers.get('Authorization')).toBe('Bearer jwt');
    request.flush({});
  });
  it('sends password reset requests with the API contract bodies', () => {
    service.forgotPassword('a@escuela.mx').subscribe((result) => expect(result.message).toBe('ok'));
    const forgot = http.expectOne(`${environment.apiUrl}/auth/forgot-password`);
    expect(forgot.request.method).toBe('POST');
    expect(forgot.request.body).toEqual({ email: 'a@escuela.mx' });
    forgot.flush({ message: 'ok' });
    service.resetPassword('token', 'clave1234').subscribe();
    const reset = http.expectOne(`${environment.apiUrl}/auth/reset-password`);
    expect(reset.request.body).toEqual({ token: 'token', password: 'clave1234' });
    reset.flush({});
  });
});
