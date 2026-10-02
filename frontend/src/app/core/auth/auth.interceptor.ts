import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { ToastService } from '../services/toast.service';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const token = localStorage.getItem('aulatools_token');
  const router = inject(Router);
  const toast = inject(ToastService);
  return next(
    token ? request.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : request,
  ).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401) {
        localStorage.removeItem('aulatools_token');
        void router.navigateByUrl('/login');
      }
      const message = error.error?.error?.message;
      toast.show(
        typeof message === 'string' ? message : 'Ocurrió un error al procesar la solicitud.',
      );
      return throwError(() => error);
    }),
  );
};
