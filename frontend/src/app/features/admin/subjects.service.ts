import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class SubjectsService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/subjects`;
  list(): Observable<any[]> {
    return this.http.get<any[]>(this.url);
  }
  save(id: number | null, data: unknown): Observable<any> {
    return id ? this.http.put(`${this.url}/${id}`, data) : this.http.post(this.url, data);
  }
  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
  students(id: number): Observable<any[]> {
    return this.http.get<any[]>(`${this.url}/${id}/students`);
  }
  enroll(id: number, student_id: number): Observable<any> {
    return this.http.post(`${this.url}/${id}/students`, { student_id });
  }
  unenroll(id: number, student_id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}/students/${student_id}`);
  }
}
