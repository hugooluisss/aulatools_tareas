import { Injectable, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class ToastService {
  readonly message = signal('');
  show(message: string): void {
    this.message.set(message);
    setTimeout(() => this.message.set(''), 3000);
  }
}
