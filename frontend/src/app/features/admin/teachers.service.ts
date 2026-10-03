import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Page } from '../../core/models/page';

export interface TeacherRow {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  status?: string;
  photo_url?: string;
  [key: string]: unknown;
}

@Injectable({ providedIn: 'root' })
export class TeachersService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/users/teachers`;
  list(page = 1): Observable<Page<TeacherRow>> {
    return this.http.get<Page<TeacherRow>>(this.url, { params: { page, per_page: 20 } });
  }
  all(): Observable<TeacherRow[]> {
    return this.http.get<Page<TeacherRow>>(this.url, { params: { per_page: 100 } }).pipe(map((page) => page.items));
  }
  save(id: number | null, data: unknown): Observable<any> {
    return id ? this.http.put(`${this.url}/${id}`, data) : this.http.post(this.url, data);
  }
  photo(id: number, photo: Blob): Observable<any> {
    const body = new FormData();
    body.append('photo', photo, 'teacher.jpg');
    return this.http.post(`${this.url}/${id}/photo`, body);
  }
  removePhoto(id: number): Observable<any> {
    return this.http.delete(`${this.url}/${id}/photo`);
  }
  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
  reset(id: number, new_password: string): Observable<any> {
    return this.http.put(`${environment.apiUrl}/users/${id}/password`, { new_password });
  }
}
