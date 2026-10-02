import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { TokenStorageService } from '../auth/token-storage.service';

export const roleGuard: CanActivateFn = (route) => {
  const tokens = inject(TokenStorageService);
  const router = inject(Router);
  const role = tokens.getRole();
  return role && (!route.data['roles'] || route.data['roles'].includes(role))
    ? true
    : router.createUrlTree(['/login']);
};
