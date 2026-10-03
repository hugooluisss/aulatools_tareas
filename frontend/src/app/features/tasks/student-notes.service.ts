import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '../../../environments/environment';

export interface StudentNote {
  id: number;
  student_id: number;
  author: { id: number; first_name: string; last_name: string; role: string };
  body: string;
  created_at: string;
}

@Injectable({ providedIn: 'root' })
export class StudentNotesService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/users/students`;

  list(studentId: number) {
    return this.http.get<{
      data: StudentNote[];
      meta: { page: number; per_page: number; total: number };
    }>(`${this.url}/${studentId}/notes`, {
      params: new HttpParams().set('page', 1).set('per_page', 100),
    });
  }

  add(studentId: number, body: string) {
    return this.http.post<StudentNote>(`${this.url}/${studentId}/notes`, { body });
  }
}
