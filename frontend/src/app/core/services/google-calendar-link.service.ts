import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class GoogleCalendarLinkService {
  build(title: string, details: string, startsAt: Date, endsAt: Date): string {
    const dates = `${this.format(startsAt)}/${this.format(endsAt)}`;
    const query = new URLSearchParams({ action: 'TEMPLATE', text: title, details, dates });
    return `https://calendar.google.com/calendar/render?${query.toString()}`;
  }
  private format(date: Date): string {
    return date
      .toISOString()
      .replace(/[-:]/g, '')
      .replace(/\.\d{3}/, '');
  }
}
