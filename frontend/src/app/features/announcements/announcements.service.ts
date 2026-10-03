import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Page } from '../../core/models/page';

export interface Announcement {
  id: number;
  title: string;
  body: string;
  starts_on: string;
  ends_on: string;
}

@Injectable({ providedIn: 'root' })
export class AnnouncementsService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiUrl}/announcements`;

  list(page = 1): Observable<Page<Announcement>> {
    return this.http.get<Page<Announcement>>(this.url, { params: { page, per_page: 20 } });
  }

  active(page = 1): Observable<Page<Announcement>> {
    return this.http.get<Page<Announcement>>(`${this.url}/active`, { params: { page, per_page: 20 } });
  }

  save(id: number | null, announcement: Omit<Announcement, 'id'>): Observable<unknown> {
    return id
      ? this.http.put(`${this.url}/${id}`, announcement)
      : this.http.post(this.url, announcement);
  }

  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
}
