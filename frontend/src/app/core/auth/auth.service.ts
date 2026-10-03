import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TokenStorageService } from './token-storage.service';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly tokens = inject(TokenStorageService);
  registerSchool(data: unknown): Observable<unknown> {
    return this.http.post(`${environment.apiUrl}/auth/register-school`, data);
  }
  login(data: { email: string; password: string }): Observable<{ token: string }> {
    return this.http
      .post<{ token: string }>(`${environment.apiUrl}/auth/login`, data)
      .pipe(tap(({ token }) => this.tokens.setToken(token)));
  }
  forgotPassword(email: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${environment.apiUrl}/auth/forgot-password`, { email });
  }
  resetPassword(token: string, password: string): Observable<unknown> {
    return this.http.post(`${environment.apiUrl}/auth/reset-password`, { token, password });
  }
  changePassword(data: { current_password: string; new_password: string }): Observable<unknown> {
    return this.http.post(`${environment.apiUrl}/auth/change-password`, data);
  }
  logout(): void {
    this.tokens.clear();
  }
}
