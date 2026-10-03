import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { finalize } from 'rxjs';
import { BusyButtonsService } from './busy-buttons.service';

export const busyButtonsInterceptor: HttpInterceptorFn = (request, next) => {
  const finish = inject(BusyButtonsService).start(request.url, request.method);
  return next(request).pipe(finalize(() => finish?.()));
};
