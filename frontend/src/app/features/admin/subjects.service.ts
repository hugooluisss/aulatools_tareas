import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface SubjectRow {
  id: string;
  code: string;
  name: string;
  plan_id: string | null;
  plan_name: string | null;
  teacher_id: string;
  status: 'active' | 'inactive';
  students_count: string;
}

@Injectable({ providedIn: 'root' })
export class SubjectsService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/subjects`;
  list(status?: 'active'): Observable<SubjectRow[]> {
    return this.http.get<SubjectRow[]>(this.url, { params: status ? { status } : {} });
  }
  save(id: number | null, data: unknown): Observable<any> {
    return id ? this.http.put(`${this.url}/${id}`, data) : this.http.post(this.url, data);
  }
  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
  students(id: number, cycleId?: number): Observable<any[]> {
    return this.http.get<any[]>(`${this.url}/${id}/students`, {
      params: cycleId ? { cycle_id: cycleId } : {},
    });
  }
  enrollBulk(id: number, cycle_id: number, student_ids: number[]): Observable<any[]> {
    return this.http.post<any[]>(`${this.url}/${id}/students/bulk`, { cycle_id, student_ids });
  }
  enroll(id: number, student_id: number, cycle_id: number): Observable<any> {
    return this.http.post(`${this.url}/${id}/students`, { student_id, cycle_id });
  }
  unenroll(id: number, student_id: number, cycle_id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}/students/${student_id}`, {
      params: { cycle_id },
    });
  }
}
