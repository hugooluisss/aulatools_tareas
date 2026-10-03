import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Page } from '../../core/models/page';

export interface StudentRow {
  id: string;
  first_name: string;
  last_name: string;
  enrollment_number: string;
  email: string;
  birth_date: string;
  status: string;
  enrollment?: { group_name: string; cycle_name: string };
  [key: string]: unknown;
}

@Injectable({ providedIn: 'root' })
export class StudentsService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/users/students`;
  list(page = 1): Observable<Page<StudentRow>> {
    return this.http.get<Page<StudentRow>>(this.url, { params: { page, per_page: 20 } });
  }
  all(): Observable<StudentRow[]> {
    return this.http.get<Page<StudentRow>>(this.url, { params: { per_page: 100 } }).pipe(map((page) => page.items));
  }
  save(id: number | null, data: unknown): Observable<any> {
    return id ? this.http.put(`${this.url}/${id}`, data) : this.http.post(this.url, data);
  }
  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
  status(id: number, status: string): Observable<any> {
    return this.http.patch(`${this.url}/${id}/status`, { status });
  }
  reset(id: number, new_password: string): Observable<any> {
    return this.http.put(`${environment.apiUrl}/users/${id}/password`, { new_password });
  }
}
