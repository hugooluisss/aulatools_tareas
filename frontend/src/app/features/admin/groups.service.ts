import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface GroupInput {
  name: string;
  cycle_id: number;
  subject_ids: number[];
  teacher_id: number | null;
}

export interface GroupRow {
  id: string;
  name: string;
  cycle_id: string;
  cycle_name: string;
  teacher_id: string | null;
  subjects: { id: string; name: string }[];
}

@Injectable({ providedIn: 'root' })
export class GroupsService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/groups`;
  list(): Observable<GroupRow[]> {
    return this.http.get<GroupRow[]>(this.url);
  }
  save(id: number | null, data: GroupInput): Observable<GroupRow> {
    return id
      ? this.http.put<GroupRow>(`${this.url}/${id}`, data)
      : this.http.post<GroupRow>(this.url, data);
  }
  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
  enroll(id: number, student_id: number): Observable<any> {
    return this.http.post(`${this.url}/${id}/students`, { student_id });
  }
}
