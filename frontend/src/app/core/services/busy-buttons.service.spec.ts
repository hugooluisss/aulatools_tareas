import { TestBed } from '@angular/core/testing';
import { BusyButtonsService } from './busy-buttons.service';

describe('BusyButtonsService', () => {
  let service: BusyButtonsService;
  let host: HTMLDivElement;

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [BusyButtonsService] });
    service = TestBed.inject(BusyButtonsService);
    host = document.createElement('div');
    document.body.append(host);
  });

  afterEach(() => host.remove());

  function click(button: HTMLButtonElement): void {
    button.dispatchEvent(new MouseEvent('click', { bubbles: true }));
  }

  it('ignores clicks without requests and disables until all linked requests finish', () => {
    const button = document.createElement('button');
    host.append(button);
    click(button);
    expect(button.disabled).toBe(false);
    const finishFirst = service.start('/items', 'POST')!;
    const finishSecond = service.start('/items', 'POST')!;
    expect(button.disabled).toBe(true);
    expect(button.getAttribute('data-busy-label')).toBe('Guardando…');
    finishFirst();
    expect(button.disabled).toBe(true);
    finishSecond();
    expect(button.disabled).toBe(false);
    expect(button.hasAttribute('data-busy-label')).toBe(false);
  });

  it('restores after errors, leaves table buttons unlabeled, and selects labels', () => {
    const table = document.createElement('table');
    table.innerHTML = '<tbody><tr><td><button>Delete</button></td></tr></tbody>';
    host.append(table);
    const button = table.querySelector('button')!;
    click(button);
    const finish = service.start('/items/1', 'DELETE')!;
    expect(button.disabled).toBe(true);
    expect(button.hasAttribute('data-busy-label')).toBe(false);
    finish(); // finalize also runs when the HTTP observable errors.
    expect(button.disabled).toBe(false);

    for (const [url, method, label] of [
      ['/items', 'GET', 'Leyendo…'],
      ['/items', 'PATCH', 'Guardando…'],
      ['/auth/login', 'POST', 'Procesando…'],
    ]) {
      const outside = document.createElement('button');
      host.append(outside);
      click(outside);
      const done = service.start(url, method)!;
      expect(outside.getAttribute('data-busy-label')).toBe(label);
      done();
    }
  });
});
