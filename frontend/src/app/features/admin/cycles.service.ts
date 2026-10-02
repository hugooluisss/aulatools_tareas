import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class CyclesService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/cycles`;
  list(): Observable<any> {
    return this.http.get<any>(this.url);
  }
  save(id: number | null, data: unknown): Observable<any> {
    return id ? this.http.put(`${this.url}/${id}`, data) : this.http.post(this.url, data);
  }
  finish(id: number): Observable<any> {
    return this.http.post(`${this.url}/${id}/finish`, {});
  }
}
