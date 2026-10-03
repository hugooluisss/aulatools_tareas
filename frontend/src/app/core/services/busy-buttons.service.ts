import { Injectable } from '@angular/core';

type BusyButtonState = { count: number; wasDisabled: boolean; label?: string };

@Injectable({ providedIn: 'root' })
export class BusyButtonsService {
  private clickedButton?: HTMLButtonElement;
  private clickedAt = 0;
  private readonly states = new WeakMap<HTMLButtonElement, BusyButtonState>();

  constructor() {
    document.addEventListener(
      'click',
      (event) => {
        const target = event.target;
        this.clickedButton = target instanceof Element ? (target.closest('button') ?? undefined) : undefined;
        this.clickedAt = performance.now();
      },
      true,
    );
  }

  start(url: string, method: string): (() => void) | undefined {
    const button = this.clickedButton;
    if (!button || performance.now() - this.clickedAt > 50) return;
    if (!button.isConnected) return;

    let state = this.states.get(button);
    if (!state) {
      state = { count: 0, wasDisabled: button.disabled };
      this.states.set(button, state);
      button.disabled = true;
      button.classList.add('is-busy');
      if (!button.closest('td, th') && !button.hasAttribute('data-busy-silent')) {
        state.label = button.getAttribute('data-busy-label') ?? this.label(url, method);
        button.setAttribute('data-busy-label', state.label);
      }
    }
    state.count++;

    return () => {
      if (--state!.count) return;
      button.classList.remove('is-busy');
      if (state!.label !== undefined && button.getAttribute('data-busy-label') === state!.label) {
        button.removeAttribute('data-busy-label');
      }
      if (!state!.wasDisabled) button.disabled = false;
      this.states.delete(button);
    };
  }

  private label(url: string, method: string): string {
    if (url.includes('/auth/') || method === 'DELETE') return 'Procesando…';
    if (method === 'GET') return 'Leyendo…';
    return 'Guardando…';
  }
}
