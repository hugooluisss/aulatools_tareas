import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class TokenStorageService {
  private readonly key = 'aulatools_token';
  getToken(): string | null {
    return localStorage.getItem(this.key);
  }
  setToken(token: string): void {
    localStorage.setItem(this.key, token);
  }
  clear(): void {
    localStorage.removeItem(this.key);
  }
  getRole(): string | null {
    const token = this.getToken();
    try {
      return token ? (JSON.parse(atob(token.split('.')[1])).role ?? null) : null;
    } catch {
      return null;
    }
  }
}
