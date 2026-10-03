import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, from, mergeMap, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ReportsService {
  private readonly http = inject(HttpClient);

  attendance(params: {
    group_id: number;
    subject_id?: number;
    start_date: string;
    days: number;
  }): Observable<Blob> {
    let query = new HttpParams()
      .set('group_id', params.group_id)
      .set('start_date', params.start_date)
      .set('days', params.days);
    if (params.subject_id) query = query.set('subject_id', params.subject_id);
    return this.http
      .get(`${environment.apiUrl}/reports/attendance`, { params: query, responseType: 'blob' })
      .pipe(
        catchError((error: HttpErrorResponse) =>
          from(error.error instanceof Blob ? error.error.text() : Promise.resolve(''))
            .pipe(mergeMap((body) => {
              let message = error.message;
              try {
                message = JSON.parse(body).error?.message ?? message;
              } catch {}
              return throwError(() => new Error(message));
            })),
        ),
      );
  }
}
