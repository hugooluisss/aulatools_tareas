import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class SchoolService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/school`;
  get(): Observable<School> {
    return this.http.get<School>(this.url);
  }
  update(data: Omit<School, 'id' | 'logo_url'>): Observable<School> {
    return this.http.put<School>(this.url, data);
  }
  uploadLogo(file: File): Observable<School> {
    const body = new FormData();
    body.append('logo', file);
    return this.http.post<School>(`${this.url}/logo`, body);
  }
  removeLogo(): Observable<School> {
    return this.http.delete<School>(`${this.url}/logo`);
  }
}

export interface School {
  id: number;
  name: string;
  address: string | null;
  phone: string | null;
  email: string | null;
  logo_url: string | null;
}
