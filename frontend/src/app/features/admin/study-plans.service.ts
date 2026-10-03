import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface StudyPlan {
  id: string;
  code: string;
  name: string;
  status: 'active' | 'inactive';
  subjects_count?: string;
}

@Injectable({ providedIn: 'root' })
export class StudyPlansService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/study-plans`;

  list(): Observable<StudyPlan[]> {
    return this.http.get<StudyPlan[]>(this.url);
  }

  save(
    id: number | null,
    data: Pick<StudyPlan, 'code' | 'name' | 'status'>,
  ): Observable<StudyPlan> {
    return id
      ? this.http.put<StudyPlan>(`${this.url}/${id}`, data)
      : this.http.post<StudyPlan>(this.url, data);
  }

  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
}
