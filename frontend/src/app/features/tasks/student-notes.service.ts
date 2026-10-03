import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { Observable } from 'rxjs';
import { Page } from '../../core/models/page';

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

  list(studentId: number, page = 1): Observable<Page<StudentNote>> {
    return this.http.get<Page<StudentNote>>(`${this.url}/${studentId}/notes`, {
      params: new HttpParams().set('page', page).set('per_page', 20),
    });
  }

  add(studentId: number, body: string) {
    return this.http.post<StudentNote>(`${this.url}/${studentId}/notes`, { body });
  }
}
