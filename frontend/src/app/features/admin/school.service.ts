import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class SchoolService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/school`;
  get(): Observable<{ id: number; name: string }> {
    return this.http.get<{ id: number; name: string }>(this.url);
  }
  update(name: string): Observable<{ id: number; name: string }> {
    return this.http.put<{ id: number; name: string }>(this.url, { name });
  }
}
