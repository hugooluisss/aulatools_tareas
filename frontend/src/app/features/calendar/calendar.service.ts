import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface CalendarItem {
  id: number;
  type: 'event' | 'task_due';
  title: string;
  description: string;
  starts_at: string;
  ends_at: string;
  subject_id: number | null;
  google_calendar_url?: string;
}

export interface CalendarEvent {
  id: number;
  subject_id: number | null;
  title: string;
  description: string;
  starts_at: string;
  ends_at: string;
}

@Injectable({ providedIn: 'root' })
export class CalendarService {
  private readonly http = inject(HttpClient);
  private readonly api = `${environment.apiUrl}/calendar`;

  list(from: string, to: string): Observable<CalendarItem[]> {
    return this.http.get<CalendarItem[]>(this.api, {
      params: new HttpParams().set('from', from).set('to', to),
    });
  }

  events(): Observable<CalendarEvent[]> {
    return this.http.get<CalendarEvent[]>(`${this.api}/events`);
  }

  save(id: number | null, event: Omit<CalendarEvent, 'id'>): Observable<unknown> {
    return id
      ? this.http.put(`${this.api}/events/${id}`, event)
      : this.http.post(`${this.api}/events`, event);
  }

  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.api}/events/${id}`);
  }
}
