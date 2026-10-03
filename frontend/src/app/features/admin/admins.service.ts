import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Page } from '../../core/models/page';

export interface Admin {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  address?: string;
  phone?: string;
  [key: string]: unknown;
}

@Injectable({ providedIn: 'root' })
export class AdminsService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/users/admins`;

  list(page = 1): Observable<Page<Admin>> {
    return this.http.get<Page<Admin>>(this.url, { params: { page, per_page: 20 } });
  }

  all(): Observable<Admin[]> {
    return this.http
      .get<Page<Admin>>(this.url, { params: { per_page: 100 } })
      .pipe(map((page) => page.items));
  }

  save(id: number | null, data: unknown): Observable<any> {
    return id ? this.http.put(`${this.url}/${id}`, data) : this.http.post(this.url, data);
  }

  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }

  reset(id: number, new_password: string): Observable<any> {
    return this.http.put(`${environment.apiUrl}/users/${id}/password`, { new_password });
  }
}
