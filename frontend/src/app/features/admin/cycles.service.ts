import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Page } from '../../core/models/page';

export interface CycleRow {
  id: string;
  name: string;
  starts_on: string;
  ends_on: string;
  status: string;
}

@Injectable({ providedIn: 'root' })
export class CyclesService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/cycles`;
  list(page = 1): Observable<Page<CycleRow>> {
    return this.http.get<Page<CycleRow>>(this.url, { params: { page, per_page: 20 } });
  }
  all(): Observable<CycleRow[]> {
    return this.http.get<Page<CycleRow>>(this.url, { params: { per_page: 100 } }).pipe(map((page) => page.items));
  }
  save(id: number | null, data: unknown): Observable<any> {
    return id ? this.http.put(`${this.url}/${id}`, data) : this.http.post(this.url, data);
  }
  finish(id: number): Observable<any> {
    return this.http.post(`${this.url}/${id}/finish`, {});
  }
}
