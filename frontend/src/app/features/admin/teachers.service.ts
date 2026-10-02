import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class TeachersService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/users/teachers`;
  list(): Observable<any> {
    return this.http.get<any>(this.url);
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
