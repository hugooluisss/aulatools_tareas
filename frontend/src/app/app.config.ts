import { ApplicationConfig, inject, provideAppInitializer,
  provideBrowserGlobalErrorListeners } from '@angular/core';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { authInterceptor } from './core/auth/auth.interceptor';
import { busyButtonsInterceptor } from './core/services/busy-buttons.interceptor';
import { BusyButtonsService } from './core/services/busy-buttons.service';

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideAppInitializer(() => {
      inject(BusyButtonsService);
    }),
    provideRouter(routes),
    provideHttpClient(withInterceptors([authInterceptor, busyButtonsInterceptor])),
  ],
};
