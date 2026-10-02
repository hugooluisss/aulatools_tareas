import { TestBed } from '@angular/core/testing';
import { ToastService } from './toast.service';

describe('ToastService', () => {
  it('publishes the toast message', () => {
    const service = TestBed.inject(ToastService);
    service.show('Guardado');
    expect(service.message()).toBe('Guardado');
  });
});
