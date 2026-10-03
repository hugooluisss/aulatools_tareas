import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Inscription {
  id: number | string;
  student_id: number | string;
  student_first_name: string;
  student_last_name: string;
  cycle_id: number | string;
  cycle_name: string;
  group_id: number | string;
  group_name: string;
}

export interface EnrollmentCandidate {
  student_id: number | string;
  first_name: string;
  last_name: string;
  cycle_id?: number | string;
  cycle_name?: string;
  group_id?: number | string;
  group_name?: string;
}

@Injectable({ providedIn: 'root' })
export class InscriptionsService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/enrollments`;

  list(filters: { cycle_id?: number; student_id?: number } = {}): Observable<Inscription[]> {
    let params = new HttpParams();
    if (filters.cycle_id) params = params.set('cycle_id', filters.cycle_id);
    if (filters.student_id) params = params.set('student_id', filters.student_id);
    return this.http.get<Inscription[]>(this.url, { params });
  }

  create(data: {
    student_id: number;
    cycle_id: number;
    group_id: number;
  }): Observable<Inscription> {
    return this.http.post<Inscription>(this.url, data);
  }

  update(id: number, group_id: number): Observable<Inscription> {
    return this.http.put<Inscription>(`${this.url}/${id}`, { group_id });
  }

  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }

  aspirants(): Observable<EnrollmentCandidate[]> {
    return this.http.get<EnrollmentCandidate[]>(`${this.url}/aspirants`);
  }

  reenrollable(): Observable<EnrollmentCandidate[]> {
    return this.http.get<EnrollmentCandidate[]>(`${this.url}/reenrollable`);
  }

  bulk(data: {
    type: 'enrollment' | 'reenrollment';
    student_ids: number[];
    cycle_id: number;
    group_id: number;
  }): Observable<Inscription[]> {
    return this.http.post<Inscription[]>(`${this.url}/bulk`, data);
  }
}
